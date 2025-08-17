<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Enrollment;
use App\Services\SePayService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PaymentGatewayController extends Controller
{
    protected $sePayService;
    protected $paymentService;

    public function __construct(SePayService $sePayService, PaymentService $paymentService)
    {
        $this->sePayService = $sePayService;
        $this->paymentService = $paymentService;
    }

    /**
     * Trang thanh toán cho học viên (public)
     */
    public function showPaymentPage($enrollmentId)
    {
        try {
            $enrollment = Enrollment::with(['student', 'courseItem', 'payments'])
                ->findOrFail($enrollmentId);

            // Tính số tiền còn thiếu
            $totalPaid = $enrollment->getTotalPaidAmount();
            $remaining = $enrollment->final_fee - $totalPaid;

            if ($remaining <= 0) {
                return view('payment.completed', compact('enrollment'));
            }

            return view('payment.gateway', compact('enrollment', 'remaining'));
        } catch (\Exception $e) {
            Log::error('Error showing payment page: ' . $e->getMessage());
            return view('payment.error', ['message' => 'Không tìm thấy thông tin thanh toán']);
        }
    }

    /**
     * Khởi tạo thanh toán (public)
     */
    public function initiatePayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'enrollment_id' => 'required|exists:enrollments,id',
                'amount' => 'required|numeric|min:1000',
            ]);

            $enrollment = Enrollment::with(['student', 'courseItem'])->findOrFail($validated['enrollment_id']);
            
            // Kiểm tra số tiền còn thiếu
            $totalPaid = $enrollment->getTotalPaidAmount();
            $remaining = $enrollment->final_fee - $totalPaid;
            
            if ($remaining <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Học viên đã thanh toán đủ học phí'
                ], 400);
            }

            if ($validated['amount'] > $remaining) {
                return response()->json([
                    'success' => false,
                    'message' => 'Số tiền thanh toán không được vượt quá số tiền còn thiếu'
                ], 400);
            }

            // Tạo payment record
            $payment = Payment::create([
                'enrollment_id' => $enrollment->id,
                'amount' => $validated['amount'],
                'payment_method' => 'sepay',
                'payment_date' => now(),
                'status' => 'pending',
                'notes' => 'Thanh toán qua SePay - Đang chờ xác nhận'
            ]);

            // Tạo QR code
            $qrResult = $this->sePayService->generateQR($payment);

            if (!$qrResult['success']) {
                $payment->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể tạo mã QR thanh toán'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'payment_id' => $payment->id,
                    'qr_data' => $qrResult['data'],
                    'enrollment' => [
                        'id' => $enrollment->id,
                        'student_name' => $enrollment->student->full_name,
                        'course_name' => $enrollment->courseItem->name,
                        'final_fee' => $enrollment->final_fee,
                        'total_paid' => $totalPaid,
                        'remaining_amount' => $remaining,
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error initiating payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi khởi tạo thanh toán'
            ], 500);
        }
    }

    /**
     * Webhook từ SePay (public, không cần auth)
     */
    public function webhook(Request $request)
    {
        try {
            Log::info('SePay webhook received', $request->all());

            // Validate webhook signature nếu cần
            // $this->validateWebhookSignature($request);

            $data = $request->all();
            
            // Tìm payment dựa trên transaction content
            $content = $data['content'] ?? '';
            
            // Parse content để lấy thông tin payment
            // Format: SE{student_id}_{course_id} hoặc PAY{payment_id}
            $paymentId = $this->parsePaymentIdFromContent($content);
            
            if (!$paymentId) {
                Log::warning('Cannot parse payment ID from webhook content', ['content' => $content]);
                return response()->json(['status' => 'ignored'], 200);
            }

            $payment = Payment::find($paymentId);
            
            if (!$payment) {
                Log::warning('Payment not found for webhook', ['payment_id' => $paymentId]);
                return response()->json(['status' => 'payment_not_found'], 404);
            }

            // Kiểm tra số tiền
            $webhookAmount = $data['amount'] ?? 0;
            if ($webhookAmount != $payment->amount) {
                Log::warning('Amount mismatch in webhook', [
                    'payment_id' => $paymentId,
                    'expected' => $payment->amount,
                    'received' => $webhookAmount
                ]);
                return response()->json(['status' => 'amount_mismatch'], 400);
            }

            // Cập nhật payment status
            DB::beginTransaction();
            try {
                $payment->update([
                    'status' => 'confirmed',
                    'transaction_reference' => $data['transaction_id'] ?? null,
                    'notes' => 'Thanh toán qua SePay - Đã xác nhận tự động'
                ]);

                // Log thành công
                Log::info('Payment confirmed via webhook', [
                    'payment_id' => $payment->id,
                    'enrollment_id' => $payment->enrollment_id,
                    'amount' => $payment->amount
                ]);

                DB::commit();
                
                return response()->json(['status' => 'success'], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Webhook processing error: ' . $e->getMessage(), $request->all());
            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Kiểm tra trạng thái thanh toán (public)
     */
    public function checkPaymentStatus($paymentId)
    {
        try {
            $payment = Payment::with(['enrollment.student', 'enrollment.courseItem'])
                ->findOrFail($paymentId);

            return response()->json([
                'success' => true,
                'data' => [
                    'payment_id' => $payment->id,
                    'status' => $payment->status,
                    'amount' => $payment->amount,
                    'payment_date' => $payment->payment_date->format('d/m/Y H:i:s'),
                    'enrollment' => [
                        'id' => $payment->enrollment->id,
                        'student_name' => $payment->enrollment->student->full_name,
                        'course_name' => $payment->enrollment->courseItem->name,
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy thông tin thanh toán'
            ], 404);
        }
    }

    /**
     * Parse payment ID từ webhook content
     */
    private function parsePaymentIdFromContent($content)
    {
        // Format 1: SE{student_id}_{course_id} - tìm payment pending tương ứng
        if (preg_match('/^SE(\d+)_(\d+)$/', $content, $matches)) {
            $studentId = $matches[1];
            $courseId = $matches[2];
            
            $payment = Payment::whereHas('enrollment', function($q) use ($studentId, $courseId) {
                $q->where('student_id', $studentId)
                  ->where('course_item_id', $courseId);
            })
            ->where('status', 'pending')
            ->where('payment_method', 'sepay')
            ->orderBy('created_at', 'desc')
            ->first();
            
            return $payment ? $payment->id : null;
        }
        
        // Format 2: PAY{payment_id}
        if (preg_match('/^PAY(\d+)$/', $content, $matches)) {
            return (int) $matches[1];
        }
        
        return null;
    }

    /**
     * Trang kết quả thanh toán
     */
    public function paymentResult(Request $request)
    {
        $paymentId = $request->get('payment_id');
        $status = $request->get('status', 'pending');
        
        if ($paymentId) {
            $payment = Payment::with(['enrollment.student', 'enrollment.courseItem'])
                ->find($paymentId);
            
            if ($payment) {
                return view('payment.result', compact('payment', 'status'));
            }
        }
        
        return view('payment.error', ['message' => 'Không tìm thấy thông tin thanh toán']);
    }
}

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

    // Webhook method đã chuyển sang SePayWebhookController để tập trung xử lý
    // Xóa method này để tránh nhầm lẫn

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

    // parsePaymentIdFromContent method đã chuyển sang SePayWebhookController

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

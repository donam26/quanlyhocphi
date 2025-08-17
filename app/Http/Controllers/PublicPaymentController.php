<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Payment;
use App\Services\SePayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PublicPaymentController extends Controller
{
    protected $sePayService;

    public function __construct(SePayService $sePayService)
    {
        $this->sePayService = $sePayService;
    }

    /**
     * Hiển thị trang thanh toán public cho học viên
     */
    public function showPaymentPage($token)
    {
        try {
            // Decode token để lấy enrollment_id
            $enrollmentId = $this->decodePaymentToken($token);
            
            if (!$enrollmentId) {
                return view('public.payment-error', [
                    'message' => 'Link thanh toán không hợp lệ hoặc đã hết hạn'
                ]);
            }

            // Lấy thông tin enrollment
            $enrollment = Enrollment::with(['student', 'courseItem', 'payments'])
                ->find($enrollmentId);

            if (!$enrollment) {
                return view('public.payment-error', [
                    'message' => 'Không tìm thấy thông tin ghi danh'
                ]);
            }

            // Tính toán số tiền còn thiếu
            $totalPaid = $enrollment->payments->where('status', 'confirmed')->sum('amount');
            $remaining = $enrollment->final_fee - $totalPaid;

            if ($remaining <= 0) {
                return view('public.payment-success', [
                    'enrollment' => $enrollment,
                    'message' => 'Bạn đã thanh toán đủ học phí'
                ]);
            }

            // Kiểm tra xem có payment pending nào không
            $pendingPayment = $enrollment->payments()
                ->where('status', 'pending')
                ->where('payment_method', 'sepay')
                ->orderBy('created_at', 'desc')
                ->first();

            $qrData = null;
            if ($pendingPayment) {
                // Sử dụng payment pending hiện tại
                $qrResult = $this->sePayService->generateQR($pendingPayment);
                if ($qrResult['success']) {
                    $qrData = $qrResult['data'];
                }
            }

            return view('public.payment-page', [
                'enrollment' => $enrollment,
                'remaining' => $remaining,
                'totalPaid' => $totalPaid,
                'qrData' => $qrData,
                'pendingPayment' => $pendingPayment,
                'token' => $token
            ]);

        } catch (\Exception $e) {
            Log::error('Error showing public payment page', [
                'token' => $token,
                'error' => $e->getMessage()
            ]);

            return view('public.payment-error', [
                'message' => 'Có lỗi xảy ra. Vui lòng thử lại sau.'
            ]);
        }
    }

    /**
     * Tạo payment mới cho học viên
     */
    public function createPayment(Request $request, $token)
    {
        try {
            $validated = $request->validate([
                'amount' => 'required|numeric|min:1000'
            ]);

            // Decode token để lấy enrollment_id
            $enrollmentId = $this->decodePaymentToken($token);
            
            if (!$enrollmentId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Link thanh toán không hợp lệ'
                ], 400);
            }

            $enrollment = Enrollment::with(['student', 'courseItem', 'payments'])
                ->find($enrollmentId);

            if (!$enrollment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy thông tin ghi danh'
                ], 404);
            }

            // Tính toán số tiền còn thiếu
            $totalPaid = $enrollment->payments->where('status', 'confirmed')->sum('amount');
            $remaining = $enrollment->final_fee - $totalPaid;

            if ($remaining <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn đã thanh toán đủ học phí'
                ], 400);
            }

            if ($validated['amount'] > $remaining) {
                return response()->json([
                    'success' => false,
                    'message' => 'Số tiền thanh toán không được vượt quá số tiền còn thiếu: ' . number_format($remaining) . ' VND'
                ], 400);
            }

            // Hủy các payment pending cũ
            $enrollment->payments()
                ->where('status', 'pending')
                ->where('payment_method', 'sepay')
                ->update(['status' => 'cancelled']);

            // Tạo payment mới
            $payment = Payment::create([
                'enrollment_id' => $enrollment->id,
                'amount' => $validated['amount'],
                'payment_method' => 'sepay',
                'payment_date' => now(),
                'status' => 'pending',
                'notes' => 'Thanh toán qua link public - Đang chờ xác nhận'
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
                    'qr_data' => $qrResult['data']
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating public payment', [
                'token' => $token,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo thanh toán'
            ], 500);
        }
    }

    /**
     * Kiểm tra trạng thái thanh toán
     */
    public function checkPaymentStatus($token, $paymentId)
    {
        try {
            $enrollmentId = $this->decodePaymentToken($token);
            
            if (!$enrollmentId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Link không hợp lệ'
                ], 400);
            }

            $payment = Payment::where('id', $paymentId)
                ->where('enrollment_id', $enrollmentId)
                ->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy thanh toán'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'status' => $payment->status,
                    'amount' => $payment->amount,
                    'payment_date' => $payment->payment_date->format('d/m/Y H:i'),
                    'is_confirmed' => $payment->status === 'confirmed'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error checking payment status', [
                'token' => $token,
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra'
            ], 500);
        }
    }

    /**
     * Tạo token cho enrollment
     */
    public static function generatePaymentToken($enrollmentId)
    {
        // Tạo token với thời hạn 30 ngày
        $data = [
            'enrollment_id' => $enrollmentId,
            'expires_at' => now()->addDays(30)->timestamp
        ];

        return base64_encode(json_encode($data));
    }

    /**
     * Decode token để lấy enrollment_id
     */
    private function decodePaymentToken($token)
    {
        try {
            $data = json_decode(base64_decode($token), true);
            
            if (!$data || !isset($data['enrollment_id']) || !isset($data['expires_at'])) {
                return null;
            }

            // Kiểm tra thời hạn
            if ($data['expires_at'] < now()->timestamp) {
                return null;
            }

            return $data['enrollment_id'];
        } catch (\Exception $e) {
            return null;
        }
    }
}

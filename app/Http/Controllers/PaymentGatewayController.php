<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Enrollment;
use App\Services\PaymentGatewayService;
use Illuminate\Support\Facades\Log;

class PaymentGatewayController extends Controller
{
    protected $paymentGatewayService;

    /**
     * Khởi tạo controller
     */
    public function __construct(PaymentGatewayService $paymentGatewayService)
    {
        // Trang thanh toán không yêu cầu đăng nhập
        $this->middleware('auth')->except(['show', 'webhook']);
        $this->paymentGatewayService = $paymentGatewayService;
    }
    
    /**
     * Hiển thị trang thanh toán
     */
    public function show(Payment $payment)
    {
        // Nếu thanh toán đã được xác nhận hoặc đã hủy, chỉ hiển thị thông tin
        if (in_array($payment->status, ['confirmed', 'cancelled'])) {
            return view('payments.gateway.show', compact('payment'));
        }
        
        try {
            // Đảm bảo đã load mối quan hệ enrollment
            if (!$payment->relationLoaded('enrollment')) {
                $payment->load('enrollment.student', 'enrollment.courseItem');
            }
            
            // Xác định transaction ID
            $transactionId = null;
            if ($payment->enrollment) {
                // Nếu payment có enrollment, dùng pattern HP{student_id}_{course_id}
                $transactionId = config('sepay.pattern') . $payment->enrollment->student->id . '_' . $payment->enrollment->courseItem->id;
            } else {
                // Nếu payment thông thường, dùng PAY{payment_id}
                $transactionId = 'PAY' . str_pad($payment->id, 6, '0', STR_PAD_LEFT);
            }
            
            // Tạo URL QR SePay trực tiếp
            $qrImageUrl = 'https://qr.sepay.vn/img?acc=' . config('sepay.bank_number')
                . '&bank=' . config('sepay.bank_code')
                . '&amount=' . $payment->amount
                . '&des=' . urlencode($transactionId)
                . '&template=compact';
            
            Log::info('Generated SePay QR URL for payment page', ['url' => $qrImageUrl]);
            
            // Chuẩn bị dữ liệu để hiển thị
            $viewData = [
                'payment' => $payment,
                'qr_image' => $qrImageUrl,
                'bank_info' => [
                    'bank_name' => config('sepay.bank_name'),
                    'bank_account' => config('sepay.bank_number'),
                    'account_owner' => config('sepay.account_owner'),
                    'amount' => $payment->amount,
                    'content' => $transactionId
                ]
            ];
            
            return view('payments.gateway.show', $viewData);
        } catch (\Exception $e) {
            Log::error('Error in show payment gateway: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return view('payments.gateway.show', [
                'payment' => $payment,
                'qr_error' => 'Đã xảy ra lỗi khi tạo mã QR. Vui lòng sử dụng thông tin chuyển khoản thủ công.'
            ]);
        }
    }

    /**
     * Khởi tạo thanh toán qua cổng thanh toán
     */
    public function initiate(Request $request, Enrollment $enrollment)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1000',
            'redirect_url' => 'nullable|url'
        ]);
        
        $amount = $validated['amount'];
        $redirectUrl = $validated['redirect_url'] ?? null;
        
        // Kiểm tra số tiền thanh toán không vượt quá số tiền còn thiếu
        $totalPaid = $enrollment->payments()->where('status', 'confirmed')->sum('amount');
        $remainingAmount = $enrollment->final_fee - $totalPaid;
        
        if ($amount > $remainingAmount) {
            return back()->withInput()->withErrors(['amount' => 'Số tiền thanh toán không được vượt quá số tiền còn thiếu: ' . number_format($remainingAmount) . ' đ']);
        }
        
        $result = $this->paymentGatewayService->initiatePayment($enrollment, $amount, $redirectUrl);
        
        if (!$result['success']) {
            return back()->withInput()->withErrors(['error' => 'Không thể khởi tạo thanh toán: ' . ($result['message'] ?? 'Đã xảy ra lỗi')]);
        }
        
        // Chuyển hướng đến trang thanh toán
        return redirect($result['payment_url']);
    }

    /**
     * Kiểm tra trạng thái thanh toán
     */
    public function checkStatus(Payment $payment)
    {
        if (!$payment->transaction_id) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy mã giao dịch'
            ]);
        }
        
        $result = $this->paymentGatewayService->checkPaymentStatus($payment->transaction_id);
        
        return response()->json($result);
    }

    /**
     * Hủy thanh toán
     */
    public function cancel(Payment $payment)
    {
        if (!$payment->transaction_id) {
            return redirect()->back()->withErrors(['error' => 'Không tìm thấy mã giao dịch']);
        }
        
        $result = $this->paymentGatewayService->cancelPayment($payment->transaction_id);
        
        if (!$result['success']) {
            return redirect()->back()->withErrors(['error' => 'Không thể hủy thanh toán: ' . ($result['message'] ?? 'Đã xảy ra lỗi')]);
        }
        
        return redirect()->back()->with('success', 'Đã hủy thanh toán thành công');
    }

    /**
     * Webhook nhận thông báo từ cổng thanh toán
     */
    public function webhook(Request $request)
    {
        Log::info('Received webhook', ['payload' => $request->all()]);
        
        $result = $this->paymentGatewayService->handleWebhook($request->all());
        
        if (!$result['success']) {
            Log::error('Webhook processing failed', ['message' => $result['message']]);
            return response()->json(['success' => false, 'message' => $result['message']], 400);
        }
        
        Log::info('Webhook processed successfully', ['payment_id' => $result['payment']->id]);
        return response()->json(['success' => true]);
    }
}

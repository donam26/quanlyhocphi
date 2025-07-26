<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\Enrollment; // Added missing import
use Illuminate\Support\Facades\Mail; // Added missing import
use App\Mail\PaymentConfirmationMail; // Added missing import
use App\Services\SePayService; // Added missing import

class PaymentGatewayController extends Controller
{
    /**
     * Khởi tạo controller
     */
    public function __construct()
    {
        // Trang thanh toán không yêu cầu đăng nhập
        $this->middleware('auth')->except(['show', 'webhook']);
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
     * Hiển thị trang thanh toán trực tiếp (không qua bảng Payment)
     */
    public function showDirectPaymentGateway(Enrollment $enrollment)
    {
        try {
            // Tạo đối tượng Payment tạm thời (không lưu vào DB)
            $payment = new Payment([
                'enrollment_id' => $enrollment->id,
                'amount' => $enrollment->getRemainingAmount(),
                'payment_method' => 'bank_transfer',
                'status' => 'pending',
                'notes' => 'Thanh toán trực tiếp'
            ]);
            
            $payment->enrollment = $enrollment;
            
            // Xác định transaction ID
            $transactionId = config('sepay.pattern') . $enrollment->student->id . '_' . $enrollment->courseItem->id;
            
            // Tạo URL QR SePay trực tiếp
            $qrImageUrl = 'https://qr.sepay.vn/img?acc=' . config('sepay.bank_number')
                . '&bank=' . config('sepay.bank_code')
                . '&amount=' . $payment->amount
                . '&des=' . urlencode($transactionId)
                . '&template=compact';
            
            Log::info('Generated SePay QR URL for direct payment', ['url' => $qrImageUrl]);
            
            // Chuẩn bị dữ liệu để hiển thị
            $viewData = [
                'enrollment' => $enrollment,
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
            
            return view('payments.gateway.direct', $viewData);
        } catch (\Exception $e) {
            Log::error('Error in show direct payment gateway: ' . $e->getMessage(), [
                'enrollment_id' => $enrollment->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Tạo đối tượng Payment tạm thời nếu chưa có
            if (!isset($payment)) {
                $payment = new Payment([
                    'enrollment_id' => $enrollment->id,
                    'amount' => $enrollment->getRemainingAmount(),
                    'payment_method' => 'bank_transfer',
                    'status' => 'pending',
                    'notes' => 'Thanh toán trực tiếp'
                ]);
                
                $payment->enrollment = $enrollment;
            }
            
            return view('payments.gateway.direct', [
                'enrollment' => $enrollment,
                'payment' => $payment,
                'qr_error' => 'Đã xảy ra lỗi khi tạo mã QR. Vui lòng sử dụng thông tin chuyển khoản thủ công.'
            ]);
        }
    }

    /**
     * Tạo mã QR cho thanh toán
     */
    public function generateQrCode(Payment $payment)
    {
        try {
            // Đảm bảo payment có enrollment được load
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
            
            Log::info('Generated SePay QR URL', ['url' => $qrImageUrl]);
            
            // Trả về kết quả
            return response()->json([
                'success' => true,
                'data' => [
                    'qr_image' => $qrImageUrl,
                    'bank_name' => config('sepay.bank_name'),
                    'bank_account' => config('sepay.bank_number'),
                    'account_owner' => config('sepay.account_owner'),
                    'amount' => $payment->amount,
                    'content' => $transactionId
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to generate QR code: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Trả về thông tin chuyển khoản thủ công khi không thể tạo QR
            return response()->json([
                'success' => false,
                'message' => 'Không thể tạo mã QR. Vui lòng sử dụng thông tin chuyển khoản thủ công.',
                'data' => [
                    'bank_name' => config('sepay.bank_name'),
                    'bank_account' => config('sepay.bank_number'),
                    'account_owner' => config('sepay.account_owner'),
                    'amount' => $payment->amount,
                    'content' => $payment->enrollment ? 
                        ('HP' . $payment->enrollment->student->id . '_' . $payment->enrollment->courseItem->id) : 
                        ('PAY' . str_pad($payment->id, 6, '0', STR_PAD_LEFT))
                ]
            ]);
        }
    }

    /**
     * Kiểm tra trạng thái thanh toán
     */
    public function checkPaymentStatus(Payment $payment)
    {
        try {
            // Load enrollment nếu chưa được load
            if (!$payment->relationLoaded('enrollment')) {
                $payment->load('enrollment.student', 'enrollment.courseItem');
            }
            
            $sePayService = app(SePayService::class);
            
            // Xác định transaction ID dựa trên loại payment
            $transactionId = null;
            
            if ($payment->enrollment) {
                // Nếu payment có enrollment, dùng pattern HP{student_id}_{course_id}
                $transactionId = config('sepay.pattern') . $payment->enrollment->student->id . '_' . $payment->enrollment->courseItem->id;
            } else {
                // Nếu payment thông thường, dùng PAY{payment_id}
                $transactionId = 'PAY' . str_pad($payment->id, 6, '0', STR_PAD_LEFT);
            }
            
            // Log thông tin cho debug
            Log::info('Checking payment status', [
                'payment_id' => $payment->id,
                'transaction_id' => $transactionId,
            ]);
            
            // Kiểm tra trạng thái thanh toán
            $status = $sePayService->checkPaymentStatus($transactionId);
            
            // Nếu thanh toán đã được xác nhận, cập nhật trạng thái trong DB
            if ($status['success'] && $status['confirmed'] && $payment->status !== 'confirmed') {
                $payment->update([
                    'status' => 'confirmed',
                    'transaction_reference' => $status['transaction_reference'] ?? $transactionId,
                    'notes' => ($payment->notes ? $payment->notes . "\n" : '') . 'Thanh toán được xác nhận qua API vào ' . now(),
                ]);
                
                // Gửi email xác nhận nếu có email
                if ($payment->enrollment && $payment->enrollment->student->email) {
                    try {
                        Mail::to($payment->enrollment->student->email)
                            ->queue(new PaymentConfirmationMail($payment));
                    } catch (\Exception $e) {
                        Log::error('Error sending confirmation email', ['error' => $e->getMessage()]);
                    }
                }
            }
            
            return response()->json($status);
        } catch (\Exception $e) {
            Log::error('Failed to check payment status', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'confirmed' => false,
                'message' => 'Không thể kiểm tra trạng thái. Vui lòng thử lại sau.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Kiểm tra trạng thái thanh toán trực tiếp (không qua payment_id)
     */
    public function checkDirectPaymentStatus(Request $request)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'student_id' => 'required|exists:students,id',
                'course_id' => 'required|exists:course_items,id',
            ]);
            
            // Tìm enrollment
            $enrollment = Enrollment::where('student_id', $validated['student_id'])
                                    ->where('course_item_id', $validated['course_id'])
                                    ->first();
                                    
            if (!$enrollment) {
                return response()->json([
                    'success' => false,
                    'confirmed' => false,
                    'message' => 'Không tìm thấy thông tin ghi danh'
                ]);
            }
            
            // Kiểm tra đã thanh toán đủ học phí chưa
            $remainingAmount = $enrollment->getRemainingAmount();
            $fullyPaid = $remainingAmount <= 0;
            
            if ($fullyPaid) {
                return response()->json([
                    'success' => true,
                    'confirmed' => true,
                    'message' => 'Đã thanh toán đủ học phí',
                    'enrollment_id' => $enrollment->id,
                ]);
            }
            
            // Kiểm tra với SePayService
            $sePayService = app(SePayService::class);
            $transactionId = config('sepay.pattern') . $validated['student_id'] . '_' . $validated['course_id'];
            $status = $sePayService->checkPaymentStatus($transactionId);
            
            // Nếu thanh toán đã được xác nhận qua API
            if ($status['success'] && $status['confirmed']) {
                // Tạo thanh toán mới và xác nhận ngay
                $payment = Payment::create([
                    'enrollment_id' => $enrollment->id,
                    'amount' => min($status['amount'] ?? $remainingAmount, $remainingAmount),
                    'payment_date' => now(),
                    'payment_method' => 'bank_transfer',
                    'transaction_reference' => $status['transaction_reference'] ?? $transactionId,
                    'status' => 'confirmed',
                    'notes' => 'Thanh toán tự động từ QR code, xác nhận qua API'
                ]);
                
                // Gửi email xác nhận (không block request)
                try {
                    if ($enrollment->student->email) {
                        Mail::to($enrollment->student->email)
                            ->queue(new PaymentConfirmationMail($payment));
                    }
                } catch (\Exception $e) {
                    Log::error('Error sending confirmation email: ' . $e->getMessage());
                }
                
                return response()->json([
                    'success' => true,
                    'confirmed' => true,
                    'message' => 'Thanh toán đã được xác nhận và cập nhật',
                    'payment_id' => $payment->id,
                    'enrollment_id' => $enrollment->id,
                ]);
            }
            
            return response()->json([
                'success' => false,
                'confirmed' => false,
                'message' => 'Chưa nhận được thanh toán',
                'enrollment_id' => $enrollment->id,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error checking direct payment status: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'confirmed' => false,
                'message' => 'Có lỗi xảy ra khi kiểm tra trạng thái thanh toán',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Webhook cho thanh toán
     */
    public function webhook(Request $request)
    {
        // Xác thực webhook
        $sePayService = app(SePayService::class);
        $signature = $request->header('X-Signature');
        
        if (!$sePayService->validateWebhook($request->all(), $signature)) {
            Log::warning('Invalid webhook signature', [
                'signature' => $signature,
                'payload' => $request->all()
            ]);
            return response()->json(['status' => 'error', 'message' => 'Invalid webhook signature'], 403);
        }
        
        $data = $request->all();
        Log::info('Payment webhook received', $data);
        
        try {
            // Kiểm tra xem có nội dung chuyển khoản không
            if (empty($data['content'])) {
                return response()->json(['status' => 'error', 'message' => 'Missing transaction content'], 400);
            }
            
            // Phân tích nội dung chuyển khoản
            $parsedContent = $sePayService->parseTransactionContent($data['content']);
            
            if (!$parsedContent) {
                Log::warning('Cannot parse transaction content', ['content' => $data['content']]);
                return response()->json(['status' => 'error', 'message' => 'Invalid transaction content format'], 400);
            }
            
            // Trường hợp có payment_id (PAYxxxxx)
            if (isset($parsedContent['payment_id'])) {
                $payment = Payment::find($parsedContent['payment_id']);
                
                if (!$payment) {
                    Log::error('Payment not found', ['payment_id' => $parsedContent['payment_id']]);
                    return response()->json(['status' => 'error', 'message' => 'Payment not found'], 404);
                }
                
                // Cập nhật trạng thái thanh toán
                if ($data['status'] === 'completed' && $payment->status !== 'confirmed') {
                    $payment->update([
                        'status' => 'confirmed',
                        'transaction_reference' => $data['transaction_id'] ?? null,
                        'notes' => ($payment->notes ? $payment->notes . "\n" : '') . 'Thanh toán xác nhận qua webhook vào ' . now()
                    ]);
                    
                    // Gửi email xác nhận
                    try {
                        if ($payment->enrollment->student->email) {
                            Mail::to($payment->enrollment->student->email)
                                ->queue(new PaymentConfirmationMail($payment));
                        }
                    } catch (\Exception $e) {
                        Log::error('Error sending confirmation email', ['error' => $e->getMessage()]);
                    }
                    
                    return response()->json(['status' => 'success', 'message' => 'Payment confirmed']);
                }
                
                return response()->json(['status' => 'success', 'message' => 'No changes needed']);
            }
            
            // Trường hợp có student_id và course_id (HPxxx_yyy)
            else if (isset($parsedContent['student_id']) && isset($parsedContent['course_id'])) {
                $enrollment = Enrollment::where('student_id', $parsedContent['student_id'])
                                        ->where('course_item_id', $parsedContent['course_id'])
                                        ->first();
                
                if (!$enrollment) {
                    Log::error('Enrollment not found', [
                        'student_id' => $parsedContent['student_id'],
                        'course_id' => $parsedContent['course_id']
                    ]);
                    return response()->json(['status' => 'error', 'message' => 'Enrollment not found'], 404);
                }
                
                // Kiểm tra xem còn nợ học phí không
                $remainingAmount = $enrollment->getRemainingAmount();
                
                if ($remainingAmount <= 0) {
                    return response()->json(['status' => 'success', 'message' => 'Enrollment already fully paid']);
                }
                
                // Xác định số tiền thanh toán
                $amount = isset($data['amount']) ? (float)$data['amount'] : $remainingAmount;
                $amount = min($amount, $remainingAmount);
                
                // Tạo thanh toán mới và xác nhận ngay
                $payment = Payment::create([
                    'enrollment_id' => $enrollment->id,
                    'amount' => $amount,
                    'payment_date' => now(),
                    'payment_method' => 'bank_transfer',
                    'transaction_reference' => $data['transaction_id'] ?? null,
                    'status' => 'confirmed',
                    'notes' => 'Thanh toán tự động từ webhook'
                ]);
                
                // Gửi email xác nhận
                try {
                    if ($enrollment->student->email) {
                        Mail::to($enrollment->student->email)
                            ->queue(new PaymentConfirmationMail($payment));
                    }
                } catch (\Exception $e) {
                    Log::error('Error sending confirmation email', ['error' => $e->getMessage()]);
                }
                
                return response()->json([
                    'status' => 'success', 
                    'message' => 'Payment created and confirmed',
                    'payment_id' => $payment->id
                ]);
            }
            
            return response()->json(['status' => 'error', 'message' => 'Invalid transaction format'], 400);
            
        } catch (\Exception $e) {
            Log::error('Error processing webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }
}

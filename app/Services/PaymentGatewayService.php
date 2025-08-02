<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Payment;
use App\Services\SePayService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PaymentGatewayService
{
    protected $sePayService;
    
    public function __construct(SePayService $sePayService)
    {
        $this->sePayService = $sePayService;
    }
    
    public function initiatePayment(Enrollment $enrollment, $amount, $redirectUrl = null)
    {
        try {
            // Tạo payment record trước
            $payment = Payment::create([
                'enrollment_id' => $enrollment->id,
                'amount' => $amount,
                'payment_method' => 'sepay',
                'payment_date' => now(),
                'status' => 'pending'
            ]);
            
            // Lấy thông tin học viên
            $student = $enrollment->student;
            $courseItem = $enrollment->courseItem;
            
            // Tạo mô tả thanh toán
            $description = "Thanh toán học phí khóa {$courseItem->name} cho học viên {$student->full_name}";
            
            // Gọi đến SePay service để khởi tạo thanh toán
            $paymentInfo = $this->sePayService->createPayment([
                'amount' => $amount,
                'description' => $description,
                'metadata' => [
                    'payment_id' => $payment->id,
                    'enrollment_id' => $enrollment->id,
                    'student_id' => $student->id,
                    'course_item_id' => $courseItem->id
                ],
                'redirect_url' => $redirectUrl ?? config('app.url') . '/payments/gateway/return'
            ]);
            
            // Cập nhật payment record
            $payment->update([
                'transaction_id' => $paymentInfo['transaction_id'],
                'notes' => json_encode($paymentInfo)
            ]);
            
            return [
                'success' => true,
                'payment' => $payment,
                'payment_url' => $paymentInfo['payment_url'],
                'transaction_id' => $paymentInfo['transaction_id']
            ];
        } catch (\Exception $e) {
            Log::error('Lỗi khi khởi tạo thanh toán: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function handleWebhook($payload)
    {
        try {
            // Xác thực webhook
            if (!$this->sePayService->validateWebhook($payload)) {
                Log::error('Webhook signature không hợp lệ');
                return [
                    'success' => false,
                    'message' => 'Signature không hợp lệ'
                ];
            }
            
            // Xử lý payload
            $transactionId = $payload['transaction_id'] ?? null;
            $status = $payload['status'] ?? null;
            $metadata = $payload['metadata'] ?? [];
            
            if (!$transactionId || !$status) {
                Log::error('Webhook thiếu thông tin');
                return [
                    'success' => false,
                    'message' => 'Thiếu thông tin'
                ];
            }
            
            // Tìm payment từ transaction_id
            $payment = Payment::where('transaction_id', $transactionId)->first();
            
            if (!$payment) {
                // Nếu không tìm thấy từ transaction_id, thử tìm từ metadata
                if (isset($metadata['payment_id'])) {
                    $payment = Payment::find($metadata['payment_id']);
                }
            }
            
            if (!$payment) {
                Log::error('Không tìm thấy payment cho transaction: ' . $transactionId);
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy payment'
                ];
            }
            
            DB::beginTransaction();
            
            try {
                // Cập nhật trạng thái payment
                $payment->update([
                    'status' => $status === 'success' ? 'confirmed' : 'failed',
                    'notes' => 'Cập nhật từ webhook: ' . json_encode($payload)
                ]);
                
                DB::commit();
                
                Log::info('Xử lý webhook thành công cho transaction: ' . $transactionId);
                return [
                    'success' => true,
                    'payment' => $payment
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Lỗi khi cập nhật payment: ' . $e->getMessage());
                return [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        } catch (\Exception $e) {
            Log::error('Lỗi khi xử lý webhook: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function checkPaymentStatus($transactionId)
    {
        try {
            // Gọi API kiểm tra trạng thái thanh toán
            $statusInfo = $this->sePayService->checkStatus($transactionId);
            
            if (!$statusInfo || !isset($statusInfo['status'])) {
                return [
                    'success' => false,
                    'message' => 'Không thể kiểm tra trạng thái thanh toán'
                ];
            }
            
            // Tìm payment từ transaction_id
            $payment = Payment::where('transaction_id', $transactionId)->first();
            
            if (!$payment) {
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy payment'
                ];
            }
            
            // Cập nhật trạng thái payment nếu cần
            if ($statusInfo['status'] === 'success' && $payment->status !== 'confirmed') {
                $payment->update([
                    'status' => 'confirmed',
                    'notes' => 'Cập nhật từ check status: ' . json_encode($statusInfo)
                ]);
            } elseif ($statusInfo['status'] === 'failed' && $payment->status !== 'failed') {
                $payment->update([
                    'status' => 'failed',
                    'notes' => 'Cập nhật từ check status: ' . json_encode($statusInfo)
                ]);
            }
            
            return [
                'success' => true,
                'payment' => $payment,
                'status' => $statusInfo['status'],
                'data' => $statusInfo
            ];
        } catch (\Exception $e) {
            Log::error('Lỗi khi kiểm tra trạng thái thanh toán: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function cancelPayment($transactionId)
    {
        try {
            // Gọi API hủy thanh toán
            $cancelInfo = $this->sePayService->cancelPayment($transactionId);
            
            if (!$cancelInfo || !isset($cancelInfo['success'])) {
                return [
                    'success' => false,
                    'message' => 'Không thể hủy thanh toán'
                ];
            }
            
            if (!$cancelInfo['success']) {
                return [
                    'success' => false,
                    'message' => $cancelInfo['message'] ?? 'Không thể hủy thanh toán'
                ];
            }
            
            // Tìm payment từ transaction_id
            $payment = Payment::where('transaction_id', $transactionId)->first();
            
            if (!$payment) {
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy payment'
                ];
            }
            
            // Cập nhật trạng thái payment
            $payment->update([
                'status' => 'cancelled',
                'notes' => 'Hủy từ API: ' . json_encode($cancelInfo)
            ]);
            
            return [
                'success' => true,
                'payment' => $payment
            ];
        } catch (\Exception $e) {
            Log::error('Lỗi khi hủy thanh toán: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
} 
<?php

namespace App\Services\Payment;

use App\Contracts\PaymentRepositoryInterface;
use App\Models\Payment;
use App\Models\Enrollment;
use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * PaymentProcessingService - Chuyên biệt cho việc xử lý thanh toán
 * Tuân thủ Single Responsibility Principle
 */
class PaymentProcessingService
{
    protected PaymentRepositoryInterface $paymentRepository;

    public function __construct(PaymentRepositoryInterface $paymentRepository)
    {
        $this->paymentRepository = $paymentRepository;
    }

    /**
     * Tạo thanh toán mới
     * 
     * @param array $data
     * @return Payment
     * @throws ValidationException
     */
    public function createPayment(array $data): Payment
    {
        // Validate dữ liệu
        $validatedData = $this->validatePaymentData($data);

        DB::beginTransaction();
        
        try {
            // Chuẩn bị dữ liệu
            $paymentData = $this->preparePaymentData($validatedData);
            
            // Tạo thanh toán
            $payment = $this->paymentRepository->create($paymentData);
            
            // Xử lý logic sau khi tạo thanh toán
            $this->handlePostCreation($payment);
            
            DB::commit();
            
            return $payment;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Xác nhận thanh toán
     * 
     * @param Payment $payment
     * @return bool
     */
    public function confirmPayment(Payment $payment): bool
    {
        if ($payment->status !== PaymentStatus::PENDING) {
            throw new \Exception('Chỉ có thể xác nhận thanh toán đang chờ xử lý.');
        }

        DB::beginTransaction();
        
        try {
            // Cập nhật trạng thái thanh toán
            $result = $this->paymentRepository->confirm($payment);
            
            // Xử lý logic sau khi xác nhận
            $this->handlePostConfirmation($payment);
            
            DB::commit();
            
            return $result;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Hủy thanh toán
     * 
     * @param Payment $payment
     * @param string|null $reason
     * @return bool
     */
    public function cancelPayment(Payment $payment, ?string $reason = null): bool
    {
        if (!in_array($payment->status, [PaymentStatus::PENDING, PaymentStatus::CONFIRMED])) {
            throw new \Exception('Không thể hủy thanh toán này.');
        }

        DB::beginTransaction();
        
        try {
            // Cập nhật trạng thái thanh toán
            $result = $this->paymentRepository->cancel($payment);
            
            // Lưu lý do hủy nếu có
            if ($reason) {
                $payment->update(['cancellation_reason' => $reason]);
            }
            
            // Xử lý logic sau khi hủy
            $this->handlePostCancellation($payment);
            
            DB::commit();
            
            return $result;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Hoàn tiền
     * 
     * @param Payment $payment
     * @param float|null $amount
     * @param string|null $reason
     * @return bool
     */
    public function refundPayment(Payment $payment, ?float $amount = null, ?string $reason = null): bool
    {
        if ($payment->status !== PaymentStatus::CONFIRMED) {
            throw new \Exception('Chỉ có thể hoàn tiền cho thanh toán đã xác nhận.');
        }

        $refundAmount = $amount ?? $payment->amount;
        
        if ($refundAmount > $payment->amount) {
            throw new \Exception('Số tiền hoàn không thể lớn hơn số tiền đã thanh toán.');
        }

        DB::beginTransaction();
        
        try {
            // Cập nhật trạng thái thanh toán
            $payment->update([
                'status' => PaymentStatus::REFUNDED,
                'refund_amount' => $refundAmount,
                'refund_reason' => $reason,
                'refunded_at' => now()
            ]);
            
            // Xử lý logic sau khi hoàn tiền
            $this->handlePostRefund($payment, $refundAmount);
            
            DB::commit();
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Xử lý thanh toán từ gateway
     * 
     * @param array $gatewayData
     * @return Payment
     */
    public function processGatewayPayment(array $gatewayData): Payment
    {
        $validatedData = $this->validateGatewayData($gatewayData);

        DB::beginTransaction();
        
        try {
            // Tìm hoặc tạo thanh toán
            $payment = $this->findOrCreateGatewayPayment($validatedData);
            
            // Cập nhật thông tin từ gateway
            $this->updateFromGateway($payment, $validatedData);
            
            // Xử lý logic sau khi nhận từ gateway
            $this->handleGatewayCallback($payment, $validatedData);
            
            DB::commit();
            
            return $payment;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validate dữ liệu thanh toán
     * 
     * @param array $data
     * @return array
     * @throws ValidationException
     */
    protected function validatePaymentData(array $data): array
    {
        $rules = [
            'enrollment_id' => 'required|exists:enrollments,id',
            'amount' => 'required|numeric|min:1000',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'transaction_reference' => 'nullable|string',
            'status' => 'nullable|string',
            'notes' => 'nullable|string'
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Validate dữ liệu từ gateway
     * 
     * @param array $data
     * @return array
     * @throws ValidationException
     */
    protected function validateGatewayData(array $data): array
    {
        $rules = [
            'transaction_id' => 'required|string',
            'amount' => 'required|numeric',
            'status' => 'required|string',
            'gateway_response' => 'nullable|array'
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Chuẩn bị dữ liệu thanh toán
     * 
     * @param array $data
     * @return array
     */
    protected function preparePaymentData(array $data): array
    {
        // Set default status nếu không có
        if (!isset($data['status'])) {
            $data['status'] = PaymentStatus::PENDING;
        }

        // Set default payment method nếu không có
        if (!isset($data['payment_method'])) {
            $data['payment_method'] = PaymentMethod::CASH;
        }

        return $data;
    }

    /**
     * Xử lý logic sau khi tạo thanh toán
     * 
     * @param Payment $payment
     */
    protected function handlePostCreation(Payment $payment): void
    {
        // Có thể thêm logic như gửi notification, log, etc.
    }

    /**
     * Xử lý logic sau khi xác nhận thanh toán
     * 
     * @param Payment $payment
     */
    protected function handlePostConfirmation(Payment $payment): void
    {
        // Kiểm tra xem học viên đã thanh toán đủ chưa
        $enrollment = $payment->enrollment;
        $totalPaid = $enrollment->getTotalPaidAmount();
        
        if ($totalPaid >= $enrollment->final_fee) {
            // Có thể cập nhật trạng thái enrollment hoặc gửi thông báo
        }
    }

    /**
     * Xử lý logic sau khi hủy thanh toán
     * 
     * @param Payment $payment
     */
    protected function handlePostCancellation(Payment $payment): void
    {
        // Có thể thêm logic như gửi notification, log, etc.
    }

    /**
     * Xử lý logic sau khi hoàn tiền
     * 
     * @param Payment $payment
     * @param float $refundAmount
     */
    protected function handlePostRefund(Payment $payment, float $refundAmount): void
    {
        // Có thể thêm logic như gửi notification, log, etc.
    }

    /**
     * Tìm hoặc tạo thanh toán từ gateway
     * 
     * @param array $data
     * @return Payment
     */
    protected function findOrCreateGatewayPayment(array $data): Payment
    {
        // Logic tìm hoặc tạo thanh toán từ gateway data
        // Cần implement dựa trên cấu trúc gateway cụ thể
        throw new \Exception('Method not implemented');
    }

    /**
     * Cập nhật thanh toán từ gateway
     * 
     * @param Payment $payment
     * @param array $data
     */
    protected function updateFromGateway(Payment $payment, array $data): void
    {
        // Logic cập nhật thanh toán từ gateway data
        // Cần implement dựa trên cấu trúc gateway cụ thể
    }

    /**
     * Xử lý callback từ gateway
     * 
     * @param Payment $payment
     * @param array $data
     */
    protected function handleGatewayCallback(Payment $payment, array $data): void
    {
        // Logic xử lý callback từ gateway
        // Cần implement dựa trên cấu trúc gateway cụ thể
    }
}

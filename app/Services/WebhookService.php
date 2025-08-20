<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * Xử lý webhook SePay đơn giản
     */
    public function processSePay(array $data): array
    {
        try {
            $content = $data['content'] ?? '';
            $amount = (float) ($data['transferAmount'] ?? 0);
            $reference = $data['referenceCode'] ?? null;

            Log::info('Processing SePay webhook', [
                'content' => $content,
                'amount' => $amount,
                'reference' => $reference
            ]);

            // Tìm payment ID từ content
            $paymentId = $this->extractPaymentId($content);

            Log::info('Extracted payment ID', [
                'content' => $content,
                'payment_id' => $paymentId
            ]);

            if (!$paymentId) {
                return ['success' => false, 'message' => 'No payment ID found'];
            }

            // Tìm payment
            $payment = Payment::where('id', $paymentId)
                ->where('status', 'pending')
                ->where('payment_method', 'sepay')
                ->first();

            Log::info('Payment search result', [
                'payment_id' => $paymentId,
                'payment_found' => $payment ? true : false,
                'payment_status' => $payment ? $payment->status : null,
                'payment_method' => $payment ? $payment->payment_method : null
            ]);

            if (!$payment) {
                // Kiểm tra xem payment có tồn tại không (bất kể status)
                $anyPayment = Payment::where('id', $paymentId)->first();
                if ($anyPayment) {
                    Log::warning('Payment exists but wrong status/method', [
                        'payment_id' => $paymentId,
                        'actual_status' => $anyPayment->status,
                        'actual_method' => $anyPayment->payment_method
                    ]);
                    return ['success' => false, 'message' => 'Payment found but wrong status or method'];
                }

                return ['success' => false, 'message' => 'Payment not found'];
            }

            // Kiểm tra số tiền
            if (abs($payment->amount - $amount) > 0.01) {
                return [
                    'success' => false, 
                    'message' => 'Amount mismatch',
                    'expected' => $payment->amount,
                    'received' => $amount
                ];
            }

            // Cập nhật payment
            $payment->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'transaction_reference' => $reference,
                'notes' => 'Thanh toán qua SePay - Đã xác nhận tự động'
            ]);

            Log::info('Payment confirmed successfully', [
                'payment_id' => $payment->id,
                'amount' => $payment->amount
            ]);

            return ['success' => true, 'payment_id' => $payment->id];

        } catch (\Exception $e) {
            Log::error('Webhook processing error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Processing error'];
        }
    }

    /**
     * Extract payment ID từ content
     * Content: "CT DEN:523320101151 SEVQR29"
     * Cần lấy: 29 (từ SEVQR29)
     */
    private function extractPaymentId(string $content): ?string
    {
        // Pattern đơn giản: SEVQR{payment_id}
        if (preg_match('/SEVQR(\d+)/', $content, $matches)) {
            return $matches[1]; // "29" từ "SEVQR29"
        }

        // Pattern backup: PAY{payment_id}
        if (preg_match('/PAY(\d+)/', $content, $matches)) {
            return ltrim($matches[1], '0'); // Remove leading zeros
        }

        return null;
    }
}

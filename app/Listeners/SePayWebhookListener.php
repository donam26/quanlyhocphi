<?php

namespace App\Listeners;

use SePay\SePay\Events\SePayWebhookEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SePayWebhookListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SePayWebhookEvent $event): void
    {
        // Ghi lại toàn bộ dữ liệu webhook để debug
        Log::info('Sepay Webhook Received:', (array) $event->sePayWebhookData);

        // Chỉ xử lý khi có tiền vào
        if ($event->sePayWebhookData->transferType !== 'in') {
            Log::info('Sepay Webhook: Not an incoming transfer, skipping.');
            return;
        }

        $content = $event->sePayWebhookData->content;
        $pattern = config('sepay.pattern'); // Lấy pattern từ config, ví dụ: 'HP'
        
        // Tìm mã thanh toán trong nội dung. Ví dụ: "HP123" -> 123
        if (preg_match('/' . $pattern . '(\d+)/i', $content, $matches)) {
            $paymentId = $matches[1];
            Log::info("Sepay Webhook: Found Payment ID: {$paymentId}");

            DB::beginTransaction();
            try {
                // Dùng lockForUpdate để tránh race condition
                $payment = Payment::lockForUpdate()->find($paymentId);

                if ($payment && $payment->status !== 'confirmed') {
                    $payment->status = 'confirmed';
                    $payment->payment_method = 'sepay_qr'; // Ghi nhận phương thức
                    $payment->transaction_id = $event->sePayWebhookData->referenceCode; // Lưu mã giao dịch của ngân hàng
                    $payment->notes = ($payment->notes ? $payment->notes . "\n" : '') . "Xác nhận tự động qua Sepay lúc " . now()->toDateTimeString();
                    
                    // Cập nhật số tiền thực nhận nếu cần
                    // $payment->amount = $event->sePayWebhookData->transferAmount;
                    
                    $payment->save();

                    // TODO: Gửi email/thông báo cho học viên và admin
                    // Mail::to($payment->enrollment->student->email)->send(new PaymentSuccessMail($payment));

                    Log::info("Sepay Webhook: Payment ID {$paymentId} confirmed successfully.");
                } else {
                     Log::warning("Sepay Webhook: Payment ID {$paymentId} not found or already confirmed.");
                }
                
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Sepay Webhook: Error processing Payment ID {$paymentId}: " . $e->getMessage());
            }
        } else {
            Log::warning('Sepay Webhook: No matching payment ID found in content: ' . $content);
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SePayWebhookController extends Controller
{
    /**
     * Xử lý webhook từ SePay khi có giao dịch mới
     */
    public function handleWebhook(Request $request)
    {
        try {
            // Log toàn bộ request để debug
            Log::info('SePay Webhook received', [
                'headers' => $request->headers->all(),
                'body' => $request->all(),
                'raw_body' => $request->getContent()
            ]);

            // Validate webhook data theo docs SePay
            $data = $request->all();
            
            if (!isset($data['id']) || !isset($data['transaction_content']) || !isset($data['amount_in'])) {
                Log::warning('SePay Webhook: Missing required fields', $data);
                return response()->json(['status' => 'error', 'message' => 'Missing required fields'], 400);
            }

            // Extract thông tin giao dịch
            $transactionId = $data['id'];
            $transactionContent = $data['transaction_content'];
            $amountIn = (float) $data['amount_in'];
            $transactionDate = $data['transaction_date'] ?? now();
            $referenceNumber = $data['reference_number'] ?? null;

            Log::info('Processing SePay transaction', [
                'transaction_id' => $transactionId,
                'content' => $transactionContent,
                'amount' => $amountIn,
                'reference' => $referenceNumber
            ]);

            // Tìm payment dựa trên nội dung chuyển khoản
            $payment = $this->findPaymentByContent($transactionContent);

            if (!$payment) {
                Log::warning('SePay Webhook: No matching payment found', [
                    'content' => $transactionContent,
                    'amount' => $amountIn
                ]);
                return response()->json(['status' => 'ignored', 'message' => 'No matching payment found']);
            }

            // Kiểm tra số tiền
            if (abs($payment->amount - $amountIn) > 0.01) {
                Log::warning('SePay Webhook: Amount mismatch', [
                    'expected' => $payment->amount,
                    'received' => $amountIn,
                    'payment_id' => $payment->id
                ]);
                return response()->json(['status' => 'error', 'message' => 'Amount mismatch'], 400);
            }

            // Kiểm tra trạng thái payment
            if ($payment->status === 'confirmed') {
                Log::info('SePay Webhook: Payment already confirmed', ['payment_id' => $payment->id]);
                return response()->json(['status' => 'success', 'message' => 'Payment already confirmed']);
            }

            // Cập nhật payment
            DB::beginTransaction();
            try {
                $payment->update([
                    'status' => 'confirmed',
                    'transaction_reference' => $referenceNumber,
                    'notes' => 'Thanh toán qua SePay - Đã xác nhận tự động. Transaction ID: ' . $transactionId
                ]);

                // Log thành công
                Log::info('SePay Webhook: Payment confirmed successfully', [
                    'payment_id' => $payment->id,
                    'enrollment_id' => $payment->enrollment_id,
                    'amount' => $payment->amount,
                    'transaction_id' => $transactionId
                ]);

                DB::commit();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Payment confirmed successfully',
                    'payment_id' => $payment->id
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('SePay Webhook: Error updating payment', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('SePay Webhook: General error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Tìm payment dựa trên nội dung chuyển khoản
     */
    private function findPaymentByContent($content)
    {
        try {
            // Pattern 1: SE{student_id}_{course_id} (cho enrollment payments)
            if (preg_match('/SE(\d+)_(\d+)/', $content, $matches)) {
                $studentId = $matches[1];
                $courseId = $matches[2];
                
                Log::info('Searching payment by enrollment pattern', [
                    'student_id' => $studentId,
                    'course_id' => $courseId
                ]);

                return Payment::whereHas('enrollment', function($query) use ($studentId, $courseId) {
                    $query->where('student_id', $studentId)
                          ->where('course_item_id', $courseId);
                })
                ->where('status', 'pending')
                ->where('payment_method', 'sepay')
                ->orderBy('created_at', 'desc')
                ->first();
            }

            // Pattern 2: PAY{payment_id} (cho direct payments)
            if (preg_match('/PAY(\d+)/', $content, $matches)) {
                $paymentId = ltrim($matches[1], '0'); // Remove leading zeros
                
                Log::info('Searching payment by ID pattern', ['payment_id' => $paymentId]);

                return Payment::where('id', $paymentId)
                    ->where('status', 'pending')
                    ->where('payment_method', 'sepay')
                    ->first();
            }

            // Pattern 3: Tìm theo transaction_reference
            $payment = Payment::where('transaction_reference', $content)
                ->where('status', 'pending')
                ->where('payment_method', 'sepay')
                ->first();

            if ($payment) {
                Log::info('Found payment by transaction_reference', ['payment_id' => $payment->id]);
                return $payment;
            }

            Log::warning('No payment pattern matched', ['content' => $content]);
            return null;

        } catch (\Exception $e) {
            Log::error('Error finding payment by content', [
                'content' => $content,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Test endpoint để kiểm tra webhook
     */
    public function test(Request $request)
    {
        Log::info('SePay Webhook Test', $request->all());
        
        return response()->json([
            'status' => 'success',
            'message' => 'Webhook test successful',
            'received_data' => $request->all()
        ]);
    }
}

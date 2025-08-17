<?php

namespace App\Jobs;

use App\Models\Enrollment;
use App\Models\ReminderBatch;
use App\Mail\PaymentReminderMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendBulkPaymentReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes timeout
    public $tries = 3;

    protected $enrollmentIds;
    protected $batchId;
    protected $delayBetweenEmails;

    /**
     * Create a new job instance.
     */
    public function __construct(array $enrollmentIds, $batchId = null, $delayBetweenEmails = 2)
    {
        $this->enrollmentIds = $enrollmentIds;
        $this->batchId = $batchId;
        $this->delayBetweenEmails = $delayBetweenEmails; // Delay giữa các email (seconds)
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting bulk payment reminders', [
                'enrollment_count' => count($this->enrollmentIds),
                'batch_id' => $this->batchId
            ]);

            $batch = null;
            if ($this->batchId) {
                $batch = ReminderBatch::find($this->batchId);
                if ($batch) {
                    $batch->update(['status' => 'processing']);
                }
            }

            $successCount = 0;
            $failureCount = 0;
            $errors = [];

            foreach ($this->enrollmentIds as $index => $enrollmentId) {
                try {
                    $enrollment = Enrollment::with(['student', 'courseItem', 'payments'])
                        ->find($enrollmentId);

                    if (!$enrollment || !$enrollment->student || !$enrollment->student->email) {
                        $errors[] = "Enrollment ID {$enrollmentId}: Không tìm thấy hoặc thiếu email";
                        $failureCount++;
                        continue;
                    }

                    // Tính số tiền còn thiếu
                    $totalPaid = $enrollment->payments->where('status', 'confirmed')->sum('amount');
                    $remaining = $enrollment->final_fee - $totalPaid;

                    if ($remaining <= 0) {
                        Log::info("Enrollment ID {$enrollmentId}: Đã thanh toán đủ, bỏ qua");
                        continue;
                    }

                    // Gửi email
                    Mail::to($enrollment->student->email)
                        ->send(new PaymentReminderMail($enrollment, $remaining));

                    $successCount++;

                    Log::info("Sent reminder email", [
                        'enrollment_id' => $enrollmentId,
                        'student_email' => $enrollment->student->email,
                        'remaining_amount' => $remaining
                    ]);

                    // Delay giữa các email để tránh spam
                    if ($index < count($this->enrollmentIds) - 1) {
                        sleep($this->delayBetweenEmails);
                    }

                } catch (\Exception $e) {
                    $failureCount++;
                    $errors[] = "Enrollment ID {$enrollmentId}: " . $e->getMessage();

                    Log::error("Failed to send reminder email", [
                        'enrollment_id' => $enrollmentId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Cập nhật batch status
            if ($batch) {
                $batch->update([
                    'status' => 'completed',
                    'sent_count' => $successCount,
                    'failed_count' => $failureCount,
                    'error_details' => $errors ? json_encode($errors) : null,
                    'completed_at' => now()
                ]);
            }

            Log::info('Completed bulk payment reminders', [
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'batch_id' => $this->batchId
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk payment reminders job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'batch_id' => $this->batchId
            ]);

            // Cập nhật batch status nếu có lỗi
            if ($this->batchId) {
                $batch = ReminderBatch::find($this->batchId);
                if ($batch) {
                    $batch->update([
                        'status' => 'failed',
                        'error_details' => json_encode([$e->getMessage()])
                    ]);
                }
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendBulkPaymentReminders job failed permanently', [
            'error' => $exception->getMessage(),
            'enrollment_ids' => $this->enrollmentIds,
            'batch_id' => $this->batchId
        ]);

        // Cập nhật batch status
        if ($this->batchId) {
            $batch = ReminderBatch::find($this->batchId);
            if ($batch) {
                $batch->update([
                    'status' => 'failed',
                    'error_details' => json_encode([$exception->getMessage()])
                ]);
            }
        }
    }
}

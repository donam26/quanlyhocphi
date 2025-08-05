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
use Exception;

class SendPaymentReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $enrollment;
    public $batchId;
    public $tries = 3;
    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(Enrollment $enrollment, $batchId = null)
    {
        $this->enrollment = $enrollment;
        $this->batchId = $batchId;
        
        // Set queue name based on priority
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Load relationships
            $this->enrollment->load(['student', 'courseItem', 'payments']);
            
            // Kiểm tra student có tồn tại không
            if (!$this->enrollment->student) {
                $this->logError('Enrollment không có thông tin học viên: ID ' . $this->enrollment->id);
                $this->updateBatchProgress('failed', 'Không có thông tin học viên');
                return;
            }
            
            // Kiểm tra xem học viên có email không
            if (!$this->enrollment->student->email) {
                $this->logError('Học viên không có email: ' . $this->enrollment->student->full_name);
                $this->updateBatchProgress('failed', 'Không có email');
                return;
            }

            // Tính toán số tiền còn thiếu
            $totalPaid = $this->enrollment->payments->where('status', 'confirmed')->sum('amount');
            $remaining = $this->enrollment->final_fee - $totalPaid;

            // Nếu đã thanh toán đủ thì không gửi
            if ($remaining <= 0) {
                $this->logInfo('Học viên đã thanh toán đủ: ' . $this->enrollment->student->full_name);
                $this->updateBatchProgress('skipped', 'Đã thanh toán đủ');
                return;
            }

            // Gửi email
            Mail::to($this->enrollment->student->email)
                ->send(new PaymentReminderMail($this->enrollment, $remaining));

            $this->logInfo('Đã gửi email nhắc nhở thành công cho: ' . $this->enrollment->student->full_name);
            $this->updateBatchProgress('sent');

        } catch (Exception $e) {
            $this->logError('Lỗi gửi email cho enrollment ID ' . $this->enrollment->id . ': ' . $e->getMessage());
            $this->updateBatchProgress('failed', $e->getMessage());
            
            // Re-throw để Laravel retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        $this->logError('Job failed permanently for enrollment ID ' . $this->enrollment->id . ': ' . $exception->getMessage());
        $this->updateBatchProgress('failed', 'Job failed: ' . $exception->getMessage());
    }

    /**
     * Update batch progress
     */
    private function updateBatchProgress($status, $error = null)
    {
        if (!$this->batchId) return;

        try {
            $batch = ReminderBatch::find($this->batchId);
            if (!$batch) return;

            // Update counters
            switch ($status) {
                case 'sent':
                    $batch->increment('sent_count');
                    break;
                case 'failed':
                    $batch->increment('failed_count');
                    if ($error) {
                        $errors = $batch->errors ?? [];
                        
                        // Xử lý an toàn khi truy cập student
                        $studentName = $this->enrollment->student ? 
                            $this->enrollment->student->full_name : 
                            'Unknown Student';
                            
                        $errors[] = [
                            'enrollment_id' => $this->enrollment->id,
                            'student_name' => $studentName,
                            'error' => $error,
                            'time' => now()->toDateTimeString()
                        ];
                        $batch->update(['errors' => $errors]);
                    }
                    break;
                case 'skipped':
                    $batch->increment('skipped_count');
                    break;
            }

            $batch->increment('processed_count');

            // Check if batch is complete
            if ($batch->processed_count >= $batch->total_count) {
                $batch->update([
                    'status' => 'completed',
                    'completed_at' => now()
                ]);
                
                // Notify about completion
                $this->notifyBatchCompletion($batch);
            }
        } catch (Exception $e) {
            $this->logError('Error updating batch progress: ' . $e->getMessage());
        }
    }

    /**
     * Log info message
     */
    private function logInfo($message)
    {
        Log::channel('payment_reminders')->info($message, [
            'enrollment_id' => $this->enrollment->id,
            'batch_id' => $this->batchId,
            'student_email' => $this->enrollment->student->email ?? 'N/A'
        ]);
    }

    /**
     * Log error message
     */
    private function logError($message)
    {
        Log::channel('payment_reminders')->error($message, [
            'enrollment_id' => $this->enrollment->id,
            'batch_id' => $this->batchId,
            'student_email' => $this->enrollment->student->email ?? 'N/A'
        ]);
    }

    /**
     * Notify admin about batch completion
     */
    private function notifyBatchCompletion(ReminderBatch $batch)
    {
        // TODO: Implement real-time notification
        Log::info('Batch completed', [
            'batch_id' => $batch->id,
            'total' => $batch->total_count,
            'sent' => $batch->sent_count,
            'failed' => $batch->failed_count,
            'skipped' => $batch->skipped_count
        ]);
    }
}

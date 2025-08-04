<?php

namespace App\Jobs;

use App\Models\ReminderBatch;
use App\Models\Enrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessReminderBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $batchId;
    public $tries = 1; // Không retry batch job
    public $timeout = 300; // 5 phút

    /**
     * Create a new job instance.
     */
    public function __construct($batchId)
    {
        $this->batchId = $batchId;
        $this->onQueue('batches'); // Queue riêng cho batch processing
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $batch = ReminderBatch::find($this->batchId);
            
            if (!$batch) {
                Log::error('Batch not found: ' . $this->batchId);
                return;
            }

            // Bắt đầu xử lý batch
            $batch->start();
            
            Log::info('Starting batch processing', [
                'batch_id' => $batch->id,
                'batch_name' => $batch->name,
                'total_count' => $batch->total_count
            ]);

            // Lấy danh sách enrollments cần gửi nhắc nhở
            $enrollmentIds = $batch->enrollment_ids ?? [];
            
            if (empty($enrollmentIds)) {
                $batch->update([
                    'status' => 'completed',
                    'completed_at' => now()
                ]);
                Log::warning('Batch has no enrollments to process', ['batch_id' => $batch->id]);
                return;
            }

            // Dispatch individual jobs với delay để tránh spam
            $delay = 0;
            $delayIncrement = 2; // 2 giây giữa mỗi email

            foreach ($enrollmentIds as $enrollmentId) {
                $enrollment = Enrollment::find($enrollmentId);
                
                if (!$enrollment) {
                    Log::warning('Enrollment not found: ' . $enrollmentId);
                    continue;
                }

                // Dispatch job với delay tăng dần
                SendPaymentReminderJob::dispatch($enrollment, $batch->id)
                    ->delay(now()->addSeconds($delay))
                    ->onQueue('emails');

                $delay += $delayIncrement;
            }

            Log::info('Batch jobs dispatched', [
                'batch_id' => $batch->id,
                'jobs_dispatched' => count($enrollmentIds),
                'total_delay' => $delay . ' seconds'
            ]);

        } catch (Exception $e) {
            Log::error('Batch processing failed', [
                'batch_id' => $this->batchId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Update batch status to failed
            if (isset($batch)) {
                $batch->update([
                    'status' => 'failed',
                    'completed_at' => now()
                ]);
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Batch job failed permanently', [
            'batch_id' => $this->batchId,
            'error' => $exception->getMessage()
        ]);

        // Update batch status
        $batch = ReminderBatch::find($this->batchId);
        if ($batch) {
            $batch->update([
                'status' => 'failed',
                'completed_at' => now()
            ]);
        }
    }
}

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

            // Lấy danh sách enrollment có student hợp lệ
            $validEnrollments = Enrollment::with(['student', 'payments', 'courseItem'])
                ->whereIn('id', $enrollmentIds)
                ->whereHas('student', function($query) {
                    $query->whereNotNull('email');
                })
                ->get();
            
            // Cập nhật lại số lượng thực tế
            $validCount = $validEnrollments->count();
            $skippedCount = count($enrollmentIds) - $validCount;
            
            if ($skippedCount > 0) {
                $batch->increment('skipped_count', $skippedCount);
                $batch->increment('processed_count', $skippedCount);
                
                Log::warning('Some enrollments were skipped due to missing student or email', [
                    'batch_id' => $batch->id,
                    'skipped_count' => $skippedCount,
                    'total_enrollment_ids' => count($enrollmentIds),
                    'valid_enrollments' => $validCount
                ]);
            }
            
            if ($validEnrollments->isEmpty()) {
                $batch->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'processed_count' => $batch->total_count
                ]);
                
                Log::warning('No valid enrollments found to process', ['batch_id' => $batch->id]);
                return;
            }

            // Cập nhật tổng số thực tế
            $batch->update([
                'total_count' => $validCount + $skippedCount
            ]);

            // Dispatch individual jobs với delay để tránh spam
            $delay = 0;
            $delayIncrement = 2; // 2 giây giữa mỗi email

            foreach ($validEnrollments as $enrollment) {
                // Double-check trước khi dispatch
                if (!$enrollment->student || !$enrollment->student->email) {
                    $batch->increment('skipped_count');
                    $batch->increment('processed_count');
                    Log::warning('Skipping enrollment with missing student or email', [
                        'enrollment_id' => $enrollment->id,
                        'has_student' => (bool)$enrollment->student,
                        'has_email' => $enrollment->student ? (bool)$enrollment->student->email : false
                    ]);
                    continue;
                }
                
                // Kiểm tra xem còn thiếu học phí không
                $totalPaid = $enrollment->payments->where('status', 'confirmed')->sum('amount');
                $remaining = $enrollment->final_fee - $totalPaid;
                
                if ($remaining <= 0) {
                    $batch->increment('skipped_count');
                    $batch->increment('processed_count');
                    Log::info('Skipping fully paid enrollment', [
                        'enrollment_id' => $enrollment->id,
                        'student_name' => $enrollment->student->full_name
                    ]);
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
                'jobs_dispatched' => $validEnrollments->count(),
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

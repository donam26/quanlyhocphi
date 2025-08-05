<?php

namespace App\Services;

use App\Models\ReminderBatch;
use App\Models\Enrollment;
use App\Models\CourseItem;
use App\Jobs\ProcessReminderBatchJob;
use App\Jobs\SendPaymentReminderJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReminderService
{
    /**
     * Gửi nhắc nhở cho nhiều khóa học
     */
    public function sendBulkCourseReminders(array $courseItemIds): array
    {
        try {
            // Lấy tất cả enrollments chưa thanh toán đủ từ các khóa học
            $enrollments = $this->getUnpaidEnrollmentsByCourses($courseItemIds);
            
            if ($enrollments->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'Không có học viên nào cần gửi nhắc nhở trong các khóa học đã chọn.'
                ];
            }

            // Tạo tên batch
            $courseNames = CourseItem::whereIn('id', $courseItemIds)->pluck('name')->toArray();
            $batchName = 'Nhắc nhở ' . count($courseItemIds) . ' khóa học: ' . implode(', ', array_slice($courseNames, 0, 3));
            if (count($courseNames) > 3) {
                $batchName .= '...';
            }

            // Tạo batch
            $batch = ReminderBatch::createBatch(
                $batchName,
                'multiple_courses',
                $enrollments->pluck('id')->toArray(),
                Auth::id()
            );

            $batch->update(['course_ids' => $courseItemIds]);

            // Dispatch batch job
            ProcessReminderBatchJob::dispatch($batch->id)->onQueue('batches');

            Log::info('Bulk course reminders initiated', [
                'batch_id' => $batch->id,
                'course_count' => count($courseItemIds),
                'enrollment_count' => $enrollments->count(),
                'user_id' => Auth::id()
            ]);

            return [
                'success' => true,
                'message' => "Đã bắt đầu gửi nhắc nhở cho {$enrollments->count()} học viên trong " . count($courseItemIds) . " khóa học.",
                'batch_id' => $batch->id,
                'total_emails' => $enrollments->count()
            ];

        } catch (\Exception $e) {
            Log::error('Bulk course reminders failed', [
                'course_ids' => $courseItemIds,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Gửi nhắc nhở cho một khóa học
     */
    public function sendCourseReminders($courseItemId, ?array $enrollmentIds = null): array
    {
        try {
            $courseItem = CourseItem::find($courseItemId);
            if (!$courseItem) {
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy khóa học.'
                ];
            }

            // Nếu không chỉ định enrollments, lấy tất cả chưa thanh toán đủ
            if (empty($enrollmentIds)) {
                $enrollments = $this->getUnpaidEnrollmentsByCourses([$courseItemId]);
            } else {
                // Lấy enrollment và đảm bảo student tồn tại
                $enrollments = Enrollment::with(['student', 'payments'])
                    ->whereIn('id', $enrollmentIds)
                    ->where('course_item_id', $courseItemId)
                    ->whereHas('student') // Đảm bảo enrollment có student
                    ->get()
                    ->filter(function ($enrollment) {
                        if (!$enrollment->student) {
                            return false;
                        }
                        
                        $totalPaid = $enrollment->payments->where('status', 'confirmed')->sum('amount');
                        return $totalPaid < $enrollment->final_fee;
                    });
            }

            if ($enrollments->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'Không có học viên nào cần gửi nhắc nhở trong khóa học này.'
                ];
            }

            // Tạo batch
            $batchName = 'Nhắc nhở khóa: ' . $courseItem->name;
            $batch = ReminderBatch::createBatch(
                $batchName,
                'single_course',
                $enrollments->pluck('id')->toArray(),
                Auth::id()
            );

            $batch->update(['course_ids' => [$courseItemId]]);

            // Dispatch batch job
            ProcessReminderBatchJob::dispatch($batch->id)->onQueue('batches');

            Log::info('Course reminders initiated', [
                'batch_id' => $batch->id,
                'course_id' => $courseItemId,
                'course_name' => $courseItem->name,
                'enrollment_count' => $enrollments->count(),
                'user_id' => Auth::id()
            ]);

            return [
                'success' => true,
                'message' => "Đã bắt đầu gửi nhắc nhở cho {$enrollments->count()} học viên trong khóa {$courseItem->name}.",
                'batch_id' => $batch->id,
                'total_emails' => $enrollments->count()
            ];

        } catch (\Exception $e) {
            Log::error('Course reminders failed', [
                'course_id' => $courseItemId,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Gửi nhắc nhở cho học viên cụ thể
     */
    public function sendIndividualReminder($enrollmentId): array
    {
        try {
            // Lấy enrollment và đảm bảo student tồn tại
            $enrollment = Enrollment::with(['student', 'courseItem', 'payments'])
                ->whereHas('student')
                ->find($enrollmentId);
        
            if (!$enrollment) {
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy thông tin ghi danh hoặc ghi danh không có thông tin học viên.'
                ];
            }

            // Double check - Kiểm tra enrollment có student không
            if (!$enrollment->student) {
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy thông tin học viên.'
                ];
            }

            // Kiểm tra xem có cần gửi nhắc nhở không
            $totalPaid = $enrollment->payments->where('status', 'confirmed')->sum('amount');
            $remaining = $enrollment->final_fee - $totalPaid;

            if ($remaining <= 0) {
                return [
                    'success' => false,
                    'message' => 'Học viên đã thanh toán đủ học phí.'
                ];
            }

            if (!$enrollment->student->email) {
                return [
                    'success' => false,
                    'message' => 'Học viên không có địa chỉ email.'
                ];
            }

            // Tạo batch cho individual reminder
            $batchName = 'Nhắc nhở cá nhân: ' . $enrollment->student->full_name;
            $batch = ReminderBatch::createBatch(
                $batchName,
                'individual',
                [$enrollmentId],
                Auth::id()
            );

            // Gửi ngay lập tức (không delay)
            SendPaymentReminderJob::dispatch($enrollment, $batch->id)->onQueue('emails');

            Log::info('Individual reminder sent', [
                'batch_id' => $batch->id,
                'enrollment_id' => $enrollmentId,
                'student_name' => $enrollment->student->full_name,
                'remaining_amount' => $remaining,
                'user_id' => Auth::id()
            ]);

            return [
                'success' => true,
                'message' => "Đã gửi nhắc nhở cho {$enrollment->student->full_name}.",
                'batch_id' => $batch->id
            ];

        } catch (\Exception $e) {
            Log::error('Individual reminder failed', [
                'enrollment_id' => $enrollmentId,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Lấy danh sách enrollments chưa thanh toán đủ theo khóa học
     */
    private function getUnpaidEnrollmentsByCourses(array $courseItemIds)
    {
        try {
            $enrollments = Enrollment::with(['student', 'courseItem', 'payments'])
                ->whereIn('course_item_id', $courseItemIds)
                ->whereIn('status', ['enrolled', 'on_hold', 'active'])
                ->whereHas('student') // Đảm bảo enrollment có student
                ->get();

            return $enrollments->filter(function ($enrollment) {
                // Double check - Kiểm tra enrollment có student không
                if (!$enrollment->student) {
                    Log::warning('Enrollment without student found', ['enrollment_id' => $enrollment->id]);
                    return false;
                }
                
                // Chỉ lấy những enrollment có email và chưa thanh toán đủ
                if (!$enrollment->student->email) {
                    return false;
                }
                
                $totalPaid = $enrollment->payments->where('status', 'confirmed')->sum('amount');
                return $totalPaid < $enrollment->final_fee;
            });
        } catch (\Exception $e) {
            Log::error('Error filtering unpaid enrollments: ' . $e->getMessage());
            return collect(); // Trả về collection rỗng nếu có lỗi
        }
    }

    /**
     * Lấy thống kê batch
     */
    public function getBatchStats($batchId): ?array
    {
        $batch = ReminderBatch::find($batchId);
        
        if (!$batch) {
            return null;
        }

        return [
            'id' => $batch->id,
            'name' => $batch->name,
            'type' => $batch->type,
            'status' => $batch->status,
            'progress_percentage' => $batch->progress_percentage,
            'success_rate' => $batch->success_rate,
            'total_count' => $batch->total_count,
            'processed_count' => $batch->processed_count,
            'sent_count' => $batch->sent_count,
            'failed_count' => $batch->failed_count,
            'skipped_count' => $batch->skipped_count,
            'processing_time' => $batch->processing_time,
            'has_errors' => $batch->hasErrors(),
            'errors' => $batch->errors,
            'created_at' => $batch->created_at,
            'started_at' => $batch->started_at,
            'completed_at' => $batch->completed_at,
        ];
    }

    /**
     * Lấy danh sách batch của user
     */
    public function getUserBatches($userId, $limit = 10)
    {
        return ReminderBatch::byUser($userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($batch) {
                return $this->getBatchStats($batch->id);
            });
    }
} 
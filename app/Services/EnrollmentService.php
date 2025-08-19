<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Student;
use App\Models\CourseItem;
use App\Enums\EnrollmentStatus;
use App\Models\LearningPathProgress;
use App\Models\LearningPath;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EnrollmentService
{
    public function getEnrollments($filters = [])
    {
        $query = Enrollment::with(['student', 'courseItem', 'payments']);
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
            
            // Nếu là status waiting và cần lọc theo cần liên hệ
            if ($filters['status'] === EnrollmentStatus::WAITING->value && isset($filters['needs_contact']) && $filters['needs_contact']) {
                $query->whereNull('notes');
            }
        }
        
        if (isset($filters['course_item_id'])) {
            $query->where('course_item_id', $filters['course_item_id']);
        }

        // Hỗ trợ filter nhiều khóa học (cho khóa cha)
        if (isset($filters['course_item_ids']) && is_array($filters['course_item_ids'])) {
            $query->whereIn('course_item_id', $filters['course_item_ids']);
        }
        
        if (isset($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }
        
        if (isset($filters['payment_status'])) {
            if ($filters['payment_status'] === 'paid') {
                $query->whereHas('payments', function($q) {
                    $q->groupBy('enrollment_id')
                      ->havingRaw('SUM(CASE WHEN status = "confirmed" THEN amount ELSE 0 END) >= enrollments.final_fee');
                });
            } elseif ($filters['payment_status'] === 'partial') {
                $query->whereHas('payments', function($q) {
                    $q->where('status', 'confirmed');
                })->whereRaw('(SELECT SUM(amount) FROM payments WHERE payments.enrollment_id = enrollments.id AND payments.status = "confirmed") < enrollments.final_fee');
            } elseif ($filters['payment_status'] === 'pending') {
                $query->whereDoesntHave('payments', function($q) {
                    $q->where('status', 'confirmed');
                });
            }
        }
        
        if (isset($filters['date_from'])) {
            $query->where('enrollment_date', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('enrollment_date', '<=', $filters['date_to']);
        }
        
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('first_name', 'like', '%' . $search . '%')
                  ->orWhere('last_name', 'like', '%' . $search . '%')
                  ->orWhereRaw("CONCAT(IFNULL(first_name, ''), ' ', IFNULL(last_name, '')) LIKE ?", ['%' . $search . '%'])
                  ->orWhere('phone', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }
        
        if (isset($filters['status']) && $filters['status'] === EnrollmentStatus::WAITING->value) {
            return $query->latest('request_date')->paginate(isset($filters['per_page']) ? $filters['per_page'] : 15);
        }
        
        return $query->latest()->paginate(isset($filters['per_page']) ? $filters['per_page'] : 15);
    }

    public function getEnrollment($id)
    {
        return Enrollment::with(['student', 'courseItem', 'payments' => function($query) {
            $query->orderBy('payment_date', 'desc');
        }])->findOrFail($id);
    }

    public function createEnrollment(array $data)
    {
        DB::beginTransaction();
        
        try {
            // Tính học phí sau khi giảm giá
            $courseItem = CourseItem::findOrFail($data['course_item_id']);

            $finalFee = $courseItem->fee;
            $discountAmount = 0;

            if (isset($data['discount_percentage']) && $data['discount_percentage'] > 0) {
                $discountAmount = $finalFee * ($data['discount_percentage'] / 100);
                $finalFee -= $discountAmount;
            } elseif (isset($data['discount_amount']) && $data['discount_amount'] > 0) {
                $discountAmount = $data['discount_amount'];
                $finalFee -= $discountAmount;
            }

            // Xử lý custom_fields: tự động sao chép từ khóa học đặc biệt
            $customFields = null;
            if ($courseItem->is_special && $courseItem->custom_fields) {
                $customFields = $courseItem->custom_fields;
            } elseif (isset($data['custom_fields'])) {
                $customFields = $data['custom_fields'];
            }

            // Tạo ghi danh
            $enrollment = Enrollment::create([
                'student_id' => $data['student_id'],
                'course_item_id' => $data['course_item_id'],
                'enrollment_date' => $data['enrollment_date'] ?? now(),
                'final_fee' => $finalFee,
                'discount_percentage' => $data['discount_percentage'] ?? 0,
                'discount_amount' => $discountAmount,
                'status' => $data['status'] ?? EnrollmentStatus::ACTIVE,
                'notes' => $data['notes'] ?? null,
                'custom_fields' => $customFields
            ]);
            
            // Tạo thanh toán ban đầu nếu có
            if (isset($data['payment_amount']) && $data['payment_amount'] > 0) {
                Payment::create([
                    'enrollment_id' => $enrollment->id,
                    'amount' => $data['payment_amount'],
                    'payment_method' => $data['payment_method'] ?? 'cash',
                    'payment_date' => $data['payment_date'] ?? now(),
                    'status' => 'confirmed',
                    'notes' => $data['payment_notes'] ?? 'Thanh toán khi ghi danh'
                ]);
            }
            
            DB::commit();
            return $enrollment;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateEnrollment(Enrollment $enrollment, array $data)
    {
        DB::beginTransaction();

        try {
            \Log::info('EnrollmentService updateEnrollment - Before update:', [
                'enrollment_id' => $enrollment->id,
                'current_enrollment_date' => $enrollment->enrollment_date,
                'data_to_update' => $data
            ]);

            // Tính học phí sau khi giảm giá nếu có thay đổi
            if (isset($data['discount_percentage']) || isset($data['discount_amount']) || isset($data['final_fee'])) {
                $courseItem = $enrollment->courseItem;
                $finalFee = isset($data['final_fee']) ? $data['final_fee'] : $courseItem->fee;
                $discountAmount = $enrollment->discount_amount;

                if (isset($data['discount_percentage']) && $data['discount_percentage'] > 0) {
                    $discountAmount = $courseItem->fee * ($data['discount_percentage'] / 100);
                    $finalFee = $courseItem->fee - $discountAmount;
                } elseif (isset($data['discount_amount']) && $data['discount_amount'] > 0) {
                    $discountAmount = $data['discount_amount'];
                    $finalFee = $courseItem->fee - $discountAmount;
                }

                $data['final_fee'] = $finalFee;
                $data['discount_amount'] = $discountAmount;
            }

            $enrollment->update($data);

            \Log::info('EnrollmentService updateEnrollment - After update:', [
                'enrollment_id' => $enrollment->id,
                'new_enrollment_date' => $enrollment->fresh()->enrollment_date,
                'updated_data' => $data
            ]);

            DB::commit();
            return $enrollment->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteEnrollment(Enrollment $enrollment)
    {
        DB::beginTransaction();
        
        try {
            // Xóa các thanh toán liên quan
            $enrollment->payments()->delete();
            
            // Xóa các điểm danh liên quan
            $enrollment->attendances()->delete();
            
            // Xóa tiến độ lộ trình liên quan trực tiếp từ bảng
            LearningPathProgress::where('enrollment_id', $enrollment->id)->delete();
            
            // Xóa ghi danh
            $enrollment->delete();
            
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getUnpaidEnrollments()
    {
        return Enrollment::with(['student', 'courseItem', 'payments'])
            ->whereIn('status', [EnrollmentStatus::ACTIVE])
            ->get()
            ->filter(function ($enrollment) {
                $totalPaid = $enrollment->payments->where('status', 'confirmed')->sum('amount');
                return $totalPaid < $enrollment->final_fee;
            });
    }

    public function getStudentEnrollments(Student $student)
    {
        return $student->enrollments()
            ->with(['courseItem', 'payments'])
            ->orderBy('enrollment_date', 'desc')
            ->get();
    }

    public function getWaitingListByCourse(CourseItem $courseItem)
    {
        return Enrollment::where('course_item_id', $courseItem->id)
            ->where('status', EnrollmentStatus::WAITING->value)
            ->with('student')
            ->get();
    }

    public function moveFromWaitingToEnrolled(Enrollment $enrollment)
    {
        DB::beginTransaction();
        
        try {
            $enrollment->update([
                'status' => EnrollmentStatus::ACTIVE->value,
                'enrollment_date' => now()
            ]);
            
            DB::commit();
            return $enrollment;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    public function updateFee($enrollmentId, $newFee)
    {
        $enrollment = Enrollment::findOrFail($enrollmentId);
        $enrollment->final_fee = $newFee;
        $enrollment->save();
        
        return $enrollment;
    }
    
    public function addWaitingNote(Enrollment $enrollment, $notes)
    {
        $enrollment->notes = $notes;
        $enrollment->last_status_change = now();
        $enrollment->save();
        
        return $enrollment;
    }
    
    public function moveToWaiting(array $data)
    {
        return Enrollment::create([
            'student_id' => $data['student_id'],
            'course_item_id' => $data['course_item_id'],
            'status' => EnrollmentStatus::WAITING->value,
            'request_date' => now(),
            'notes' => $data['notes'] ?? null
        ]);
    }
    
    public function getEnrollmentsPaymentStats()
    {
        $totalPaid = Enrollment::join('payments', 'enrollments.id', '=', 'payments.enrollment_id')
            ->where('payments.status', 'confirmed')
            ->sum('payments.amount');
            
        $totalFees = Enrollment::where('status', EnrollmentStatus::ACTIVE->value)->sum('final_fee');
        $totalUnpaid = max(0, $totalFees - $totalPaid);
            
        $pendingCount = Enrollment::where('status', EnrollmentStatus::ACTIVE->value)
            ->whereRaw('(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payments.enrollment_id = enrollments.id AND payments.status = "confirmed") < enrollments.final_fee')
            ->count();
            
        return [
            'totalPaid' => $totalPaid,
            'totalFees' => $totalFees,
            'totalUnpaid' => $totalUnpaid,
            'pendingCount' => $pendingCount
        ];
    }
    
    public function getNeedsContactEnrollments()
    {
        return Enrollment::where('status', EnrollmentStatus::WAITING->value)
            ->whereNull('notes')
            ->with(['student', 'courseItem'])
            ->latest('request_date')
            ->paginate(15);
    }
    
    public function getWaitingList($filters = [])
    {
        $query = Enrollment::where('status', EnrollmentStatus::WAITING->value)
            ->with(['student', 'courseItem']);
            
        // Filters
        if (isset($filters['course_item_id'])) {
            $query->where('course_item_id', $filters['course_item_id']);
        }
        
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('student', function($q) use ($search) {
                $q->where('first_name', 'like', '%' . $search . '%')
                  ->orWhere('last_name', 'like', '%' . $search . '%')
                  ->orWhereRaw("CONCAT(IFNULL(first_name, ''), ' ', IFNULL(last_name, '')) LIKE ?", ['%' . $search . '%'])
                  ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }
        
        return $query->latest('request_date')->paginate(15);
    }

    /**
     * Get enrollment statistics
     */
    public function getEnrollmentStatistics($filters = [])
    {
        $query = Enrollment::query();

        // Apply filters
        if (isset($filters['start_date'])) {
            $query->where('enrollment_date', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('enrollment_date', '<=', $filters['end_date']);
        }

        if (isset($filters['course_item_id'])) {
            $query->where('course_item_id', $filters['course_item_id']);
        }

        $stats = [
            'total_enrollments' => $query->count(),
            'by_status' => $query->groupBy('status')
                ->selectRaw('status, count(*) as count')
                ->pluck('count', 'status')
                ->toArray(),
            'total_revenue' => $query->sum('final_fee'),
            'average_fee' => $query->avg('final_fee'),
        ];

        // Payment statistics
        $paymentStats = $query->with('payments')
            ->get()
            ->map(function ($enrollment) {
                $totalPaid = $enrollment->payments()->where('status', 'confirmed')->sum('amount');
                return [
                    'final_fee' => $enrollment->final_fee,
                    'paid_amount' => $totalPaid,
                    'remaining_amount' => $enrollment->final_fee - $totalPaid,
                    'is_fully_paid' => $totalPaid >= $enrollment->final_fee
                ];
            });

        $stats['payment_stats'] = [
            'total_paid' => $paymentStats->sum('paid_amount'),
            'total_remaining' => $paymentStats->sum('remaining_amount'),
            'fully_paid_count' => $paymentStats->where('is_fully_paid', true)->count(),
            'partially_paid_count' => $paymentStats->where('paid_amount', '>', 0)
                ->where('is_fully_paid', false)->count(),
            'unpaid_count' => $paymentStats->where('paid_amount', 0)->count(),
        ];

        return $stats;
    }

    /**
     * Get overdue enrollments (waiting too long)
     */
    public function getOverdueWaitingEnrollments($daysSinceEnrollment = 7)
    {
        $cutoffDate = Carbon::now()->subDays($daysSinceEnrollment);

        return Enrollment::where('status', EnrollmentStatus::WAITING->value)
            ->where('enrollment_date', '<', $cutoffDate)
            ->with(['student', 'courseItem'])
            ->orderBy('enrollment_date', 'asc')
            ->get();
    }

    /**
     * Auto-promote waiting enrollments based on course capacity
     */
    public function autoPromoteWaitingEnrollments($courseItemId = null)
    {
        $query = CourseItem::where('active', true);

        if ($courseItemId) {
            $query->where('id', $courseItemId);
        }

        $courses = $query->get();
        $promotedCount = 0;

        foreach ($courses as $course) {
            // Check if course has capacity limit
            if (!isset($course->custom_fields['max_students'])) {
                continue;
            }

            $maxStudents = (int) $course->custom_fields['max_students'];
            $currentEnrollments = Enrollment::where('course_item_id', $course->id)
                ->where('status', EnrollmentStatus::ACTIVE->value)
                ->count();

            $availableSlots = $maxStudents - $currentEnrollments;

            if ($availableSlots > 0) {
                // Get waiting enrollments ordered by enrollment date
                $waitingEnrollments = Enrollment::where('course_item_id', $course->id)
                    ->where('status', EnrollmentStatus::WAITING->value)
                    ->orderBy('enrollment_date', 'asc')
                    ->limit($availableSlots)
                    ->get();

                foreach ($waitingEnrollments as $enrollment) {
                    $this->moveFromWaitingToEnrolled($enrollment);
                    $promotedCount++;
                }
            }
        }

        return $promotedCount;
    }

    /**
     * Calculate enrollment capacity utilization
     */
    public function getCapacityUtilization($courseItemId = null)
    {
        $query = CourseItem::where('active', true)->where('is_leaf', true);

        if ($courseItemId) {
            $query->where('id', $courseItemId);
        }

        $courses = $query->get();
        $utilization = [];

        foreach ($courses as $course) {
            $activeEnrollments = Enrollment::where('course_item_id', $course->id)
                ->where('status', EnrollmentStatus::ACTIVE->value)
                ->count();

            $waitingEnrollments = Enrollment::where('course_item_id', $course->id)
                ->where('status', EnrollmentStatus::WAITING->value)
                ->count();

            $maxStudents = isset($course->custom_fields['max_students'])
                ? (int) $course->custom_fields['max_students']
                : null;

            $utilizationData = [
                'course_id' => $course->id,
                'course_name' => $course->name,
                'active_enrollments' => $activeEnrollments,
                'waiting_enrollments' => $waitingEnrollments,
                'max_students' => $maxStudents,
                'utilization_percentage' => $maxStudents ? ($activeEnrollments / $maxStudents) * 100 : null,
                'available_slots' => $maxStudents ? max(0, $maxStudents - $activeEnrollments) : null,
                'is_full' => $maxStudents ? $activeEnrollments >= $maxStudents : false
            ];

            $utilization[] = $utilizationData;
        }

        return $utilization;
    }

    /**
     * Get waiting list tree with counts
     */
    public function getWaitingListTree()
    {
        $courses = CourseItem::with(['children' => function($query) {
            $query->orderBy('order_index');
        }])
        ->whereNull('parent_id')
        ->orderBy('order_index')
        ->get();

        return $this->buildWaitingTreeWithCounts($courses);
    }

    private function buildWaitingTreeWithCounts($courses)
    {
        $result = [];

        foreach ($courses as $course) {
            $waitingCount = Enrollment::where('course_item_id', $course->id)
                ->where('status', EnrollmentStatus::WAITING->value)
                ->count();

            $courseData = [
                'id' => $course->id,
                'name' => $course->name,
                'parent_id' => $course->parent_id,
                'level' => $course->level,
                'is_leaf' => $course->is_leaf,
                'waiting_count' => $waitingCount,
                'children' => []
            ];

            if ($course->children->count() > 0) {
                $courseData['children'] = $this->buildWaitingTreeWithCounts($course->children);
                // Sum up children waiting counts
                $courseData['waiting_count'] += collect($courseData['children'])->sum('waiting_count');
            }

            $result[] = $courseData;
        }

        return $result;
    }

    /**
     * Transfer student to another course
     */
    public function transferStudent($enrollmentId, $data)
    {
        DB::beginTransaction();

        try {
            $enrollment = Enrollment::findOrFail($enrollmentId);
            $targetCourse = CourseItem::findOrFail($data['target_course_id']);

            // Check if student already enrolled in target course
            $existingEnrollment = Enrollment::where('student_id', $enrollment->student_id)
                ->where('course_item_id', $data['target_course_id'])
                ->first();

            if ($existingEnrollment) {
                throw new \Exception('Học viên đã được ghi danh vào khóa học này');
            }

            // Create new enrollment in target course
            $newEnrollment = Enrollment::create([
                'student_id' => $enrollment->student_id,
                'course_item_id' => $data['target_course_id'],
                'enrollment_date' => now(),
                'status' => $enrollment->status,
                'discount_percentage' => $enrollment->discount_percentage,
                'discount_amount' => $enrollment->discount_amount,
                'final_fee' => $targetCourse->fee * (1 - $enrollment->discount_percentage / 100),
                'notes' => $data['notes'] ?? 'Chuyển từ khóa học: ' . $enrollment->courseItem->name
            ]);

            // Cancel old enrollment
            $enrollment->updateStatus(EnrollmentStatus::CANCELLED->value);
            $enrollment->cancelled_at = now();
            $enrollment->notes = 'Đã chuyển sang khóa học: ' . $targetCourse->name;
            $enrollment->save();

            DB::commit();
            return $newEnrollment->load(['student', 'courseItem']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get enrollment statistics
     */
    public function getEnrollmentStats($filters = [])
    {
        $query = Enrollment::query();

        // Apply filters
        if (isset($filters['date_from'])) {
            $query->where('enrollment_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('enrollment_date', '<=', $filters['date_to']);
        }

        if (isset($filters['course_item_id'])) {
            $query->where('course_item_id', $filters['course_item_id']);
        }

        $total = $query->count();
        $waiting = $query->clone()->where('status', EnrollmentStatus::WAITING->value)->count();
        $active = $query->clone()->where('status', EnrollmentStatus::ACTIVE->value)->count();
        $completed = $query->clone()->where('status', EnrollmentStatus::COMPLETED->value)->count();
        $cancelled = $query->clone()->where('status', EnrollmentStatus::CANCELLED->value)->count();

        return [
            'total' => $total,
            'waiting' => $waiting,
            'active' => $active,
            'completed' => $completed,
            'cancelled' => $cancelled,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
            'cancellation_rate' => $total > 0 ? round(($cancelled / $total) * 100, 2) : 0
        ];
    }

    /**
     * Export enrollments to Excel
     */
    public function exportEnrollments($filters = [])
    {
        $enrollments = $this->getEnrollments($filters);

        // This would typically use Laravel Excel package
        // For now, return a simple implementation
        $filename = 'enrollments_' . date('Y-m-d_H-i-s') . '.xlsx';
        $filepath = storage_path('app/exports/' . $filename);

        // Create directory if not exists
        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        // Simple CSV export for now
        $csvFilename = str_replace('.xlsx', '.csv', $filename);
        $csvFilepath = storage_path('app/exports/' . $csvFilename);

        $file = fopen($csvFilepath, 'w');

        // Headers
        fputcsv($file, [
            'ID',
            'Học viên',
            'Số điện thoại',
            'Khóa học',
            'Ngày ghi danh',
            'Trạng thái',
            'Học phí',
            'Chiết khấu (%)',
            'Học phí cuối',
            'Ghi chú'
        ]);

        // Data
        foreach ($enrollments->items() as $enrollment) {
            fputcsv($file, [
                $enrollment->id,
                $enrollment->student->full_name ?? '',
                $enrollment->student->phone ?? '',
                $enrollment->courseItem->name ?? '',
                $enrollment->enrollment_date ? $enrollment->enrollment_date->format('d/m/Y') : '',
                $enrollment->status,
                $enrollment->courseItem->fee ?? 0,
                $enrollment->discount_percentage ?? 0,
                $enrollment->final_fee ?? 0,
                $enrollment->notes ?? ''
            ]);
        }

        fclose($file);

        return $csvFilepath;
    }

}
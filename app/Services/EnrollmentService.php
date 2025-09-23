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
            // Ki?m tra xem c� enrollment d� b? soft delete kh�ng
            $existingEnrollment = Enrollment::withTrashed()
                ->where('student_id', $data['student_id'])
                ->where('course_item_id', $data['course_item_id'])
                ->first();

            if ($existingEnrollment && $existingEnrollment->trashed()) {
                // Kh�i ph?c enrollment cu thay v� t?o m?i
                $existingEnrollment->restore();

                // C?p nh?t th�ng tin m?i
                $courseItem = CourseItem::findOrFail($data['course_item_id']);
                $originalFee = $courseItem->fee;
                $discountPercentage = isset($data['discount_percentage']) ? (float)$data['discount_percentage'] : 0;
                $discountAmount = isset($data['discount_amount']) ? (float)$data['discount_amount'] : 0;

                // T�nh discount t? percentage tru?c
                $percentageDiscount = ($originalFee * $discountPercentage) / 100;
                $totalDiscount = $percentageDiscount + $discountAmount;
                $finalFee = max(0, $originalFee - $totalDiscount);

                // X? l� custom_fields
                $customFields = null;
                if ($courseItem->is_special && $courseItem->custom_fields) {
                    $customFields = $courseItem->custom_fields;
                } elseif (isset($data['custom_fields'])) {
                    $customFields = $data['custom_fields'];
                }

                $existingEnrollment->update([
                    'enrollment_date' => $data['enrollment_date'] ?? now(),
                    'final_fee' => $finalFee,
                    'discount_percentage' => $data['discount_percentage'] ?? 0,
                    'discount_amount' => $discountAmount,
                    'status' => $data['status'] ?? EnrollmentStatus::ACTIVE,
                    'notes' => $data['notes'] ?? null,
                    'custom_fields' => $customFields,
                    'cancelled_at' => null, // Reset cancelled_at
                    'previous_status' => null,
                    'last_status_change' => now()
                ]);

                $enrollment = $existingEnrollment;
            } else {
                // T?o enrollment m?i
                $courseItem = CourseItem::findOrFail($data['course_item_id']);

                $originalFee = $courseItem->fee;
                $discountPercentage = isset($data['discount_percentage']) ? (float)$data['discount_percentage'] : 0;
                $discountAmount = isset($data['discount_amount']) ? (float)$data['discount_amount'] : 0;

                // T�nh discount t? percentage tru?c
                $percentageDiscount = ($originalFee * $discountPercentage) / 100;

                // T?ng discount = percentage discount + fixed discount amount
                $totalDiscount = $percentageDiscount + $discountAmount;

                // Final fee = original fee - total discount (kh�ng du?c �m)
                $finalFee = max(0, $originalFee - $totalDiscount);

                // Luu discount_amount th?c t? (bao g?m c? percentage v� fixed amount)
                $data['discount_amount'] = $totalDiscount;
                $data['final_fee'] = $finalFee;

                // X? l� custom_fields: t? d?ng sao ch�p t? kh�a h?c d?c bi?t
                $customFields = null;
                if ($courseItem->is_special && $courseItem->custom_fields) {
                    $customFields = $courseItem->custom_fields;
                } elseif (isset($data['custom_fields'])) {
                    $customFields = $data['custom_fields'];
                }

                // T?o ghi danh
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
            }
            
            // T?o thanh to�n ban d?u n?u c�
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

            // T�nh h?c ph� sau khi gi?m gi� n?u c� thay d?i
            if (isset($data['discount_percentage']) || isset($data['discount_amount']) || isset($data['final_fee'])) {
                $courseItem = $enrollment->courseItem;
                $originalFee = $courseItem->fee;

                // L?y gi� tr? discount t? data ho?c gi? nguy�n gi� tr? cu
                $discountPercentage = isset($data['discount_percentage']) ? (float)$data['discount_percentage'] : $enrollment->discount_percentage;
                $discountAmount = isset($data['discount_amount']) ? (float)$data['discount_amount'] : 0; // Reset v? 0 n?u kh�ng c� trong data

                // N?u c� final_fee trong data, s? d?ng tr?c ti?p (tru?ng h?p manual override)
                if (isset($data['final_fee'])) {
                    $finalFee = (float)$data['final_fee'];
                    // T�nh ngu?c discount_amount t? final_fee
                    $totalDiscount = max(0, $originalFee - $finalFee);
                    $data['discount_amount'] = $totalDiscount;
                } else {
                    // T�nh discount t? percentage tru?c
                    $percentageDiscount = ($originalFee * $discountPercentage) / 100;

                    // T?ng discount = percentage discount + fixed discount amount
                    $totalDiscount = $percentageDiscount + $discountAmount;

                    // Final fee = original fee - total discount (kh�ng du?c �m)
                    $finalFee = max(0, $originalFee - $totalDiscount);

                    // Luu discount_amount th?c t? v� final_fee
                    $data['discount_amount'] = $totalDiscount;
                    $data['final_fee'] = $finalFee;
                }
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
            // X�a m?m c�c thanh to�n v� di?m danh li�n quan
            $enrollment->payments()->delete();
            $enrollment->attendances()->delete();

            // X�a m?m ghi danh
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
                'fee' => $course->fee, // Thêm thông tin học phí
                'status' => $course->status,
                'learning_method' => $course->learning_method,
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
     * Transfer student to another course with payment handling
     */
    public function transferStudent($enrollmentId, $data)
    {
        DB::beginTransaction();

        try {
            $enrollment = Enrollment::findOrFail($enrollmentId);
            $targetCourse = CourseItem::findOrFail($data['target_course_id']);

            // Validate transfer conditions
            $this->validateTransferConditions($enrollment, $targetCourse, $data);

            // Calculate payment adjustments
            $paymentCalculation = $this->calculateTransferPayments($enrollment, $targetCourse, $data);

            // Create new enrollment
            $newEnrollment = $this->createTransferEnrollment($enrollment, $targetCourse, $paymentCalculation, $data);

            // Handle payment transfers and adjustments
            $this->handleTransferPayments($enrollment, $newEnrollment, $paymentCalculation, $data);

            // Update old enrollment status
            $this->finalizeOldEnrollment($enrollment, $targetCourse, $data);

            DB::commit();
            return [
                'new_enrollment' => $newEnrollment->load(['student', 'courseItem', 'payments']),
                'payment_summary' => $paymentCalculation,
                'transfer_type' => $paymentCalculation['transfer_type']
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validate conditions for course transfer
     */
    private function validateTransferConditions($enrollment, $targetCourse, $data)
    {
        // Check if enrollment can be transferred
        if ($enrollment->status === EnrollmentStatus::COMPLETED->value) {
            throw new \Exception('Kh�ng th? chuy?n kh�a h?c d� ho�n th�nh');
        }

        if ($enrollment->status === EnrollmentStatus::CANCELLED->value) {
            throw new \Exception('Kh�ng th? chuy?n kh�a h?c d� h?y');
        }

        // Check if target course is active
        if (!$targetCourse->isActive()) {
            throw new \Exception('Kh�a h?c d�ch kh�ng c�n ho?t d?ng');
        }

        // Check if student already has active enrollment in target course
        $activeEnrollment = Enrollment::where('student_id', $enrollment->student_id)
            ->where('course_item_id', $targetCourse->id)
            ->whereIn('status', ['active', 'waiting'])
            ->first();

        if ($activeEnrollment) {
            throw new \Exception('H?c vi�n d� c� enrollment dang ho?t d?ng trong kh�a h?c n�y');
        }

        // Validate refund policy if applicable
        if (isset($data['refund_policy']) && !in_array($data['refund_policy'], ['full', 'partial', 'none', 'credit'])) {
            throw new \Exception('Ch�nh s�ch ho�n ti?n kh�ng h?p l?');
        }
    }

    /**
     * Calculate payment adjustments for course transfer
     */
    private function calculateTransferPayments($enrollment, $targetCourse, $data)
    {
        // Get current payment information
        $totalPaid = $enrollment->getTotalPaidAmount();
        $oldFinalFee = $enrollment->final_fee;

        // Calculate new fee with same discount percentage
        $newBaseFee = $targetCourse->fee;
        $discountPercentage = $enrollment->discount_percentage ?? 0;
        $discountAmount = ($newBaseFee * $discountPercentage) / 100;
        $newFinalFee = $newBaseFee - $discountAmount;

        // Apply additional discount if provided
        if (isset($data['additional_discount_percentage'])) {
            $additionalDiscount = ($newFinalFee * $data['additional_discount_percentage']) / 100;
            $newFinalFee -= $additionalDiscount;
            $discountPercentage += $data['additional_discount_percentage'];
            $discountAmount += $additionalDiscount;
        }

        if (isset($data['additional_discount_amount'])) {
            $newFinalFee -= $data['additional_discount_amount'];
            $discountAmount += $data['additional_discount_amount'];
        }

        // Calculate difference
        $feeDifference = $newFinalFee - $totalPaid;

        // Determine transfer type and actions needed
        $transferType = $this->determineTransferType($totalPaid, $newFinalFee, $feeDifference);

        return [
            'old_fee' => $oldFinalFee,
            'new_base_fee' => $newBaseFee,
            'new_final_fee' => $newFinalFee,
            'total_paid' => $totalPaid,
            'fee_difference' => $feeDifference,
            'discount_percentage' => $discountPercentage,
            'discount_amount' => $discountAmount,
            'transfer_type' => $transferType,
            'refund_policy' => $data['refund_policy'] ?? 'credit',
            'actions_needed' => $this->getRequiredActions($transferType, $feeDifference, $data)
        ];
    }

    /**
     * Determine the type of transfer based on payment comparison
     */
    private function determineTransferType($totalPaid, $newFinalFee, $feeDifference)
    {
        if ($feeDifference > 0) {
            return 'additional_payment_required'; // C?n d�ng th�m
        } elseif ($feeDifference < 0) {
            return 'refund_required'; // C?n ho�n ti?n
        } else {
            return 'equal_transfer'; // Chuy?n d?i tr?c ti?p
        }
    }

    /**
     * Get required actions based on transfer type
     */
    private function getRequiredActions($transferType, $feeDifference, $data)
    {
        $actions = [];

        switch ($transferType) {
            case 'additional_payment_required':
                $actions[] = [
                    'type' => 'additional_payment',
                    'amount' => abs($feeDifference),
                    'description' => 'Học viên cần đóng thêm ' . number_format(abs($feeDifference)) . ' VND'
                ];
                break;

            case 'refund_required':
                $refundPolicy = $data['refund_policy'] ?? 'credit';
                if ($refundPolicy === 'full') {
                    $actions[] = [
                        'type' => 'cash_refund',
                        'amount' => abs($feeDifference),
                        'description' => 'Hoàn tiền mặt ' . number_format(abs($feeDifference)) . ' VND'
                    ];
                } elseif ($refundPolicy === 'credit') {
                    $actions[] = [
                        'type' => 'credit_balance',
                        'amount' => abs($feeDifference),
                        'description' => 'Tạo credit balance ' . number_format(abs($feeDifference)) . ' VND'
                    ];
                } elseif ($refundPolicy === 'none') {
                    $actions[] = [
                        'type' => 'no_refund',
                        'amount' => abs($feeDifference),
                        'description' => 'Không hoàn tiền, số tiền thừa sẽ được ghi nhận'
                    ];
                }
                break;

            case 'equal_transfer':
                $actions[] = [
                    'type' => 'direct_transfer',
                    'amount' => 0,
                    'description' => 'Chuyển khóa học trực tiếp, không cần điều chỉnh thanh toán'
                ];
                break;
        }

        return $actions;
    }

    /**
     * Create new enrollment for transfer or reactivate existing one
     */
    private function createTransferEnrollment($oldEnrollment, $targetCourse, $paymentCalculation, $data)
    {
        // Check if there's an existing enrollment (cancelled/completed) that we can reactivate
        $existingEnrollment = Enrollment::where('student_id', $oldEnrollment->student_id)
            ->where('course_item_id', $targetCourse->id)
            ->whereNotIn('status', ['active', 'waiting'])
            ->first();

        if ($existingEnrollment) {
            // Reactivate existing enrollment
            $existingEnrollment->update([
                'enrollment_date' => now(),
                'status' => $data['new_status'] ?? 'active',
                'discount_percentage' => $paymentCalculation['discount_percentage'],
                'discount_amount' => $paymentCalculation['discount_amount'],
                'final_fee' => $paymentCalculation['new_final_fee'],
                'notes' => $this->buildTransferNotes($oldEnrollment, $targetCourse, $paymentCalculation, $data),
                'custom_fields' => array_merge(
                    $existingEnrollment->custom_fields ?? [],
                    [
                        'transfer_from_enrollment_id' => $oldEnrollment->id,
                        'transfer_date' => now()->toDateString(),
                        'transfer_reason' => $data['reason'] ?? 'Chuy?n kh�a h?c',
                        'payment_calculation' => $paymentCalculation,
                        'reactivated_at' => now()->toDateTimeString()
                    ]
                )
            ]);

            return $existingEnrollment;
        }

        // Create new enrollment if no existing one found
        $newEnrollment = Enrollment::create([
            'student_id' => $oldEnrollment->student_id,
            'course_item_id' => $targetCourse->id,
            'enrollment_date' => now(),
            'status' => $data['new_status'] ?? 'active',
            'discount_percentage' => $paymentCalculation['discount_percentage'],
            'discount_amount' => $paymentCalculation['discount_amount'],
            'final_fee' => $paymentCalculation['new_final_fee'],
            'notes' => $this->buildTransferNotes($oldEnrollment, $targetCourse, $paymentCalculation, $data),
            'custom_fields' => [
                'transfer_from_enrollment_id' => $oldEnrollment->id,
                'transfer_date' => now()->toDateString(),
                'transfer_reason' => $data['reason'] ?? 'Chuyển khóa học',
                'payment_calculation' => $paymentCalculation
            ]
        ]);

        return $newEnrollment;
    }

    /**
     * Handle payment transfers and adjustments
     */
    private function handleTransferPayments($oldEnrollment, $newEnrollment, $paymentCalculation, $data)
    {
        $actions = $paymentCalculation['actions_needed'];

        foreach ($actions as $action) {
            switch ($action['type']) {
                case 'additional_payment':
                    $this->handleAdditionalPayment($newEnrollment, $action, $data);
                    break;

                case 'cash_refund':
                    $this->handleCashRefund($oldEnrollment, $newEnrollment, $action, $data);
                    break;

                case 'credit_balance':
                    $this->handleCreditBalance($newEnrollment, $action, $data);
                    break;

                case 'no_refund':
                    $this->handleNoRefund($oldEnrollment, $newEnrollment, $action, $data);
                    break;

                case 'direct_transfer':
                    $this->handleDirectTransfer($oldEnrollment, $newEnrollment, $data);
                    break;
            }
        }
    }

    /**
     * Handle additional payment required
     */
    private function handleAdditionalPayment($newEnrollment, $action, $data)
    {
        // Create a pending payment record for the additional amount
        if (isset($data['create_pending_payment']) && $data['create_pending_payment']) {
            Payment::create([
                'enrollment_id' => $newEnrollment->id,
                'amount' => $action['amount'],
                'payment_method' => $data['payment_method'] ?? 'cash',
                'payment_date' => $data['payment_date'] ?? now(),
                'status' => 'pending',
                'notes' => 'Thanh toán bổ sung do chuyển khóa học - ' . $action['description']
            ]);
        }
    }

    /**
     * Handle cash refund
     */
    private function handleCashRefund($oldEnrollment, $newEnrollment, $action, $data)
    {
        // Create a refund payment record (negative amount)
        Payment::create([
            'enrollment_id' => $newEnrollment->id,
            'amount' => -$action['amount'], // Negative for refund
            'payment_method' => 'refund',
            'payment_date' => now(),
            'status' => 'confirmed',
            'notes' => 'Hoàn tiền do chuyển khóa học - ' . $action['description'],
            'transaction_reference' => 'REFUND-' . $oldEnrollment->id . '-' . time()
        ]);
    }

    /**
     * Handle credit balance
     */
    private function handleCreditBalance($newEnrollment, $action, $data)
    {
        // Create a credit payment record using 'cash' method instead of 'credit'
        Payment::create([
            'enrollment_id' => $newEnrollment->id,
            'amount' => $action['amount'],
            'payment_method' => 'cash', // Changed from 'credit' to 'cash'
            'payment_date' => now(),
            'status' => 'confirmed',
            'notes' => 'Credit balance do chuyển khóa học - ' . $action['description'],
            'transaction_reference' => 'CREDIT-' . time()
        ]);
    }

    /**
     * Handle no refund case
     */
    private function handleNoRefund($oldEnrollment, $newEnrollment, $action, $data)
    {
        // Just record the overpayment in notes
        $newEnrollment->update([
            'notes' => $newEnrollment->notes . "\n\nGhi chú: " . $action['description']
        ]);
    }

    /**
     * Handle direct transfer (equal amounts)
     */
    private function handleDirectTransfer($oldEnrollment, $newEnrollment, $data)
    {
        // Transfer all confirmed payments from old to new enrollment
        $confirmedPayments = $oldEnrollment->payments()->where('status', 'confirmed')->get();

        foreach ($confirmedPayments as $payment) {
            Payment::create([
                'enrollment_id' => $newEnrollment->id,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'payment_date' => $payment->payment_date,
                'status' => 'confirmed',
                'notes' => 'Chuyển từ enrollment #' . $oldEnrollment->id . ' - ' . $payment->notes,
                'transaction_reference' => $payment->transaction_reference
            ]);
        }
    }

    private function buildTransferNotes($oldEnrollment, $targetCourse, $paymentCalculation, $data)
    {
        // Check if this is a reactivation
        $existingEnrollment = Enrollment::where('student_id', $oldEnrollment->student_id)
            ->where('course_item_id', $targetCourse->id)
            ->whereNotIn('status', ['active', 'waiting'])
            ->first();

        if ($existingEnrollment) {
            $notes = "CHUYỂN KHÓA HỌC (TÁI KÍCH HOẠT)\n";
            $notes .= "Từ: " . $oldEnrollment->courseItem->name . "\n";
            $notes .= "Sang: " . $targetCourse->name . " (Tái kích hoạt enrollment cũ)\n";
            $notes .= "Ngày chuyển: " . now()->format('d/m/Y H:i') . "\n";
            $notes .= "Enrollment cũ ID: " . $existingEnrollment->id . " (Status: " . $existingEnrollment->status . ")\n";
        } else {
            $notes = "CHUYỂN KHÓA HỌC\n";
            $notes .= "Từ: " . $oldEnrollment->courseItem->name . "\n";
            $notes .= "Sang: " . $targetCourse->name . "\n";
            $notes .= "Ngày chuyển: " . now()->format('d/m/Y H:i') . "\n";
        }

        if (isset($data['reason'])) {
            $notes .= "Lý do: " . $data['reason'] . "\n";
        }

        $notes .= "\nTHÔNG TIN THANH TOÁN:\n";
        $notes .= "Học phí cũ: " . number_format($paymentCalculation['old_fee']) . " VND\n";
        $notes .= "Học phí mới: " . number_format($paymentCalculation['new_final_fee']) . " VND\n";
        $notes .= "Đã thanh toán: " . number_format($paymentCalculation['total_paid']) . " VND\n";

        if ($paymentCalculation['fee_difference'] > 0) {
            $notes .= "Cần đóng thêm: " . number_format($paymentCalculation['fee_difference']) . " VND\n";
        } elseif ($paymentCalculation['fee_difference'] < 0) {
            $notes .= "Thừa thanh toán: " . number_format(abs($paymentCalculation['fee_difference'])) . " VND\n";
        }

        if (isset($data['notes'])) {
            $notes .= "\nGhi chú thêm: " . $data['notes'];
        }

        return $notes;
    }

    /**
     * Finalize old enrollment
     */
    private function finalizeOldEnrollment($enrollment, $targetCourse, $data)
    {
        // Update old enrollment status
        $enrollment->update([
            'status' => EnrollmentStatus::CANCELLED->value,
            'cancelled_at' => now(),
            'notes' => ($enrollment->notes ?? '') . "\n\nĐã chuyển sang khóa học: " . $targetCourse->name . " vào " . now()->format('d/m/Y H:i'),
            'custom_fields' => array_merge($enrollment->custom_fields ?? [], [
                'transferred_to_course_id' => $targetCourse->id,
                'transfer_date' => now()->toDateString(),
                'transfer_reason' => $data['reason'] ?? 'Chuyển khóa học'
            ])
        ]);

        // Cancel any pending payments for old enrollment
        $enrollment->payments()
            ->where('status', 'pending')
            ->update([
                'status' => 'cancelled',
                'notes' => 'Hủy do chuyển khóa học'
            ]);
    }

    /**
     * Get transfer history for a student
     */
    public function getTransferHistory($studentId)
    {
        return Enrollment::where('student_id', $studentId)
            ->whereJsonContains('custom_fields->transfer_from_enrollment_id', '!=', null)
            ->orWhereJsonContains('custom_fields->transferred_to_course_id', '!=', null)
            ->with(['courseItem', 'payments'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Calculate transfer cost preview (without actually transferring)
     */
    public function previewTransferCost($enrollmentId, $targetCourseId, $options = [])
    {
        $enrollment = Enrollment::findOrFail($enrollmentId);
        $targetCourse = CourseItem::findOrFail($targetCourseId);

        // Validate basic conditions
        $this->validateTransferConditions($enrollment, $targetCourse, $options);

        // Calculate payment adjustments
        $paymentCalculation = $this->calculateTransferPayments($enrollment, $targetCourse, $options);

        return [
            'current_course' => [
                'id' => $enrollment->courseItem->id,
                'name' => $enrollment->courseItem->name,
                'fee' => $enrollment->final_fee,
                'paid_amount' => $enrollment->getTotalPaidAmount()
            ],
            'target_course' => [
                'id' => $targetCourse->id,
                'name' => $targetCourse->name,
                'base_fee' => $targetCourse->fee,
                'final_fee' => $paymentCalculation['new_final_fee']
            ],
            'payment_calculation' => $paymentCalculation,
            'preview_only' => true
        ];
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
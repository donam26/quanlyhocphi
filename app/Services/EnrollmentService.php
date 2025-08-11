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
                'custom_fields' => isset($data['custom_fields']) ? $data['custom_fields'] : null
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
            
            // Tạo tiến độ học tập nếu ghi danh trực tiếp
            if ($data['status'] === EnrollmentStatus::ACTIVE->value || $data['status'] === 'enrolled' || $data['status'] === 'confirmed') {
                $this->createLearningPathProgress($enrollment);
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
            
            // Theo dõi thay đổi trạng thái
            $oldStatus = $enrollment->status;
            
            $enrollment->update($data);
            
            // Nếu trạng thái thay đổi từ waiting sang enrolled
            if ($oldStatus === EnrollmentStatus::WAITING && isset($data['status']) && $data['status'] === EnrollmentStatus::ACTIVE) {
                $this->createLearningPathProgress($enrollment);
            }
            
            DB::commit();
            return $enrollment;
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
            
            // Xóa tiến độ lộ trình liên quan
            $enrollment->learningPathProgress()->delete();
            
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
            
            $this->createLearningPathProgress($enrollment);
            
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
     * Tạo các bản ghi tiến độ học tập cho enrollment
     */
    private function createLearningPathProgress($enrollment)
    {
        // Lấy tất cả các lộ trình học tập của khóa học
        $learningPaths = $enrollment->courseItem->learningPaths;
        
        // Tạo tiến độ cho mỗi lộ trình
        foreach ($learningPaths as $path) {
            LearningPathProgress::firstOrCreate([
                'enrollment_id' => $enrollment->id,
                'learning_path_id' => $path->id
            ], [
                'is_completed' => false,
                'completed_at' => null
            ]);
        }
    }
} 
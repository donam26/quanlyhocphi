<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\CourseItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class OptimizedQueryService
{
    /**
     * Tìm kiếm học viên với cache và tối ưu hóa
     */
    public function searchStudents($filters = [], $perPage = 15)
    {
        $cacheKey = 'students_search_' . md5(serialize($filters)) . '_' . $perPage;
        
        return Cache::remember($cacheKey, 300, function () use ($filters, $perPage) {
            $query = Student::query();
            
            // Eager load relationships cần thiết
            $query->with(['province:id,name,region', 'ethnicity:id,name']);
            
            // Apply search filter với tối ưu hóa
            if (!empty($filters['search'])) {
                $query->search($filters['search']);
            }
            
            // Apply các filter khác
            $this->applyStudentFilters($query, $filters);
            
            // Sắp xếp tối ưu
            $query->orderBy('created_at', 'desc');
            
            return $query->paginate($perPage);
        });
    }

    /**
     * Lấy thống kê thanh toán với tối ưu hóa
     */
    public function getPaymentStats($courseId = null, $dateRange = null)
    {
        $cacheKey = 'payment_stats_' . ($courseId ?? 'all') . '_' . md5(serialize($dateRange));
        
        return Cache::remember($cacheKey, 600, function () use ($courseId, $dateRange) {
            $query = DB::table('payments')
                ->join('enrollments', 'payments.enrollment_id', '=', 'enrollments.id')
                ->join('students', 'enrollments.student_id', '=', 'students.id')
                ->join('course_items', 'enrollments.course_item_id', '=', 'course_items.id')
                ->where('payments.status', 'confirmed');
            
            if ($courseId) {
                $query->where('course_items.id', $courseId);
            }
            
            if ($dateRange) {
                $query->whereBetween('payments.payment_date', $dateRange);
            }
            
            return $query->select([
                DB::raw('COUNT(*) as total_payments'),
                DB::raw('SUM(payments.amount) as total_amount'),
                DB::raw('AVG(payments.amount) as avg_amount'),
                DB::raw('COUNT(DISTINCT enrollments.student_id) as unique_students')
            ])->first();
        });
    }

    /**
     * Lấy danh sách học viên chưa thanh toán đủ
     */
    public function getUnpaidStudents($courseId = null)
    {
        $query = DB::table('enrollments')
            ->join('students', 'enrollments.student_id', '=', 'students.id')
            ->join('course_items', 'enrollments.course_item_id', '=', 'course_items.id')
            ->leftJoin('payments', function($join) {
                $join->on('payments.enrollment_id', '=', 'enrollments.id')
                     ->where('payments.status', '=', 'confirmed');
            })
            ->where('enrollments.status', 'active')
            ->groupBy([
                'enrollments.id', 'students.id', 'students.first_name', 'students.last_name',
                'students.phone', 'course_items.name', 'enrollments.final_fee'
            ])
            ->havingRaw('COALESCE(SUM(payments.amount), 0) < enrollments.final_fee');
        
        if ($courseId) {
            $query->where('course_items.id', $courseId);
        }
        
        return $query->select([
            'students.id as student_id',
            'students.first_name',
            'students.last_name',
            'students.phone',
            'course_items.name as course_name',
            'enrollments.final_fee',
            DB::raw('COALESCE(SUM(payments.amount), 0) as paid_amount'),
            DB::raw('enrollments.final_fee - COALESCE(SUM(payments.amount), 0) as remaining_amount')
        ])->get();
    }

    /**
     * Lấy thống kê khóa học với số lượng học viên
     */
    public function getCourseStats()
    {
        return Cache::remember('course_stats', 1800, function () {
            return DB::table('course_items')
                ->leftJoin('enrollments', function($join) {
                    $join->on('course_items.id', '=', 'enrollments.course_item_id')
                         ->where('enrollments.status', '=', 'active');
                })
                ->where('course_items.status', 'active')
                ->groupBy('course_items.id', 'course_items.name', 'course_items.fee')
                ->select([
                    'course_items.id',
                    'course_items.name',
                    'course_items.fee',
                    DB::raw('COUNT(enrollments.id) as student_count'),
                    DB::raw('SUM(enrollments.final_fee) as expected_revenue')
                ])
                ->orderBy('student_count', 'desc')
                ->get();
        });
    }

    /**
     * Apply filters cho student query
     */
    private function applyStudentFilters($query, $filters)
    {
        if (!empty($filters['province_id'])) {
            $query->where('province_id', $filters['province_id']);
        }
        
        if (!empty($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }
        
        if (!empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }
        
        if (!empty($filters['education_level'])) {
            $query->where('education_level', $filters['education_level']);
        }
        
        if (!empty($filters['course_id'])) {
            $query->whereHas('enrollments', function($q) use ($filters) {
                $q->where('course_item_id', $filters['course_id'])
                  ->where('status', 'active');
            });
        }
        
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
    }

    /**
     * Clear cache khi có thay đổi dữ liệu
     */
    public function clearCache($type = 'all')
    {
        switch ($type) {
            case 'students':
                Cache::forget('students_search_*');
                break;
            case 'payments':
                Cache::forget('payment_stats_*');
                break;
            case 'courses':
                Cache::forget('course_stats');
                break;
            default:
                Cache::flush();
        }
    }

    /**
     * Bulk operations với transaction
     */
    public function bulkUpdateEnrollmentStatus($enrollmentIds, $status)
    {
        return DB::transaction(function () use ($enrollmentIds, $status) {
            return Enrollment::whereIn('id', $enrollmentIds)
                ->update([
                    'status' => $status,
                    'last_status_change' => now(),
                    'updated_at' => now()
                ]);
        });
    }
}

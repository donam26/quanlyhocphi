<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\CourseItem;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Student;
use Carbon\Carbon;

class DashboardService
{
    public function getSummary()
    {
        // Tổng số học viên
        $totalStudents = Student::count();
        
        // Học viên mới trong tháng
        $newStudents = Student::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
            
        // Tổng số khóa học
        $totalCourses = CourseItem::where('is_leaf', true)->count();
        
        // Tổng số ghi danh đang hoạt động
        $activeEnrollments = Enrollment::where('status', 'enrolled')->count();
        
        // Tổng doanh thu
        $totalRevenue = Payment::where('status', 'confirmed')->sum('amount');
        
        // Doanh thu tháng này
        $currentMonthRevenue = Payment::where('status', 'confirmed')
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount');
        
        // So sánh với tháng trước
        $lastMonthRevenue = Payment::where('status', 'confirmed')
            ->whereMonth('payment_date', now()->subMonth()->month)
            ->whereYear('payment_date', now()->subMonth()->year)
            ->sum('amount');
            
        $revenueGrowth = $lastMonthRevenue > 0 
            ? round((($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 2)
            : 0;
        
        return [
            'total_students' => $totalStudents,
            'new_students' => $newStudents,
            'total_courses' => $totalCourses,
            'active_enrollments' => $activeEnrollments,
            'total_revenue' => $totalRevenue,
            'current_month_revenue' => $currentMonthRevenue,
            'revenue_growth' => $revenueGrowth
        ];
    }
    
    public function getRecentActivities($limit = 10)
    {
        // Lấy các ghi danh gần đây
        $enrollments = Enrollment::with(['student', 'courseItem'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function($enrollment) {
                return [
                    'type' => 'enrollment',
                    'date' => $enrollment->enrollment_date,
                    'details' => [
                        'student_name' => $enrollment->student->full_name,
                        'student_id' => $enrollment->student->id,
                        'course_name' => $enrollment->courseItem->name,
                        'course_id' => $enrollment->courseItem->id,
                        'status' => $enrollment->status
                    ]
                ];
            });
            
        // Lấy các thanh toán gần đây
        $payments = Payment::with(['enrollment.student', 'enrollment.courseItem'])
            ->latest('payment_date')
            ->limit($limit)
            ->get()
            ->map(function($payment) {
                return [
                    'type' => 'payment',
                    'date' => $payment->payment_date,
                    'details' => [
                        'student_name' => $payment->enrollment->student->full_name,
                        'student_id' => $payment->enrollment->student->id,
                        'course_name' => $payment->enrollment->courseItem->name,
                        'course_id' => $payment->enrollment->courseItem->id,
                        'amount' => $payment->amount,
                        'method' => $payment->payment_method,
                        'status' => $payment->status
                    ]
                ];
            });
            
        // Lấy các điểm danh gần đây
        $attendances = Attendance::with(['student', 'courseItem'])
            ->latest('attendance_date')
            ->limit($limit)
            ->get()
            ->map(function($attendance) {
                return [
                    'type' => 'attendance',
                    'date' => $attendance->attendance_date,
                    'details' => [
                        'student_name' => $attendance->student->full_name,
                        'student_id' => $attendance->student->id,
                        'course_name' => $attendance->courseItem->name,
                        'course_id' => $attendance->courseItem->id,
                        'status' => $attendance->status
                    ]
                ];
            });
            
        // Gộp và sắp xếp theo thời gian
        $activities = $enrollments->concat($payments)->concat($attendances)
            ->sortByDesc('date')
            ->values()
            ->take($limit);
            
        return $activities;
    }
    
    public function getUpcomingSchedules($limit = 5)
    {
        // Lấy các lịch học sắp tới
        return Schedule::with(['courseItem'])
            ->where('start_date', '>=', now())
            ->orderBy('start_date')
            ->limit($limit)
            ->get();
    }
    
    public function getRevenueChart($months = 6)
    {
        $data = [];
        
        // Lấy dữ liệu doanh thu 6 tháng gần nhất
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthYear = $date->format('m/Y');
            
            $revenue = Payment::where('status', 'confirmed')
                ->whereMonth('payment_date', $date->month)
                ->whereYear('payment_date', $date->year)
                ->sum('amount');
                
            $data[] = [
                'month' => $monthYear,
                'revenue' => $revenue
            ];
        }
        
        return $data;
    }
    
    public function getTopCourses($limit = 5)
    {
        // Lấy top khóa học có nhiều học viên đăng ký nhất
        return CourseItem::withCount(['enrollments' => function($query) {
                $query->where('status', 'enrolled');
            }])
            ->having('enrollments_count', '>', 0)
            ->orderBy('enrollments_count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function($course) {
                // Tính tổng doanh thu của khóa học
                $revenue = Payment::whereHas('enrollment', function($query) use ($course) {
                    $query->where('course_item_id', $course->id);
                })
                ->where('status', 'confirmed')
                ->sum('amount');
                
                return [
                    'id' => $course->id,
                    'name' => $course->name,
                    'students_count' => $course->enrollments_count,
                    'revenue' => $revenue
                ];
            });
    }
    
    public function getPendingPayments()
    {
        // Lấy số lượng ghi danh chưa thanh toán đủ
        $pendingEnrollments = Enrollment::where('status', 'enrolled')
            ->whereDoesntHave('payments')
            ->orWhereHas('payments', function($query) {
                $query->groupBy('enrollment_id')
                      ->havingRaw('SUM(amount) < enrollments.final_fee');
            })
            ->with(['student', 'courseItem'])
            ->limit(10)
            ->get();
            
        $totalPending = $pendingEnrollments->sum(function($enrollment) {
            $paid = $enrollment->getTotalPaidAmount();
            return $enrollment->final_fee - $paid;
        });
        
        return [
            'count' => $pendingEnrollments->count(),
            'total_pending' => $totalPending,
            'enrollments' => $pendingEnrollments
        ];
    }
} 
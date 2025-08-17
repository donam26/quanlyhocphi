<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\CourseItem;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function getStudentReport($filters = [])
    {
        $query = Student::query();
        
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        
        $students = $query->get();
        
        return [
            'total_count' => $students->count(),
            'active_count' => Enrollment::where('status', \App\Enums\EnrollmentStatus::ACTIVE->value)->distinct('student_id')->count('student_id'),
            'recent_count' => Student::where('created_at', '>=', now()->subDays(30))->count(),
            'students' => $students
        ];
    }

    public function getEnrollmentReport($filters = [])
    {
        $query = Enrollment::with(['student', 'courseItem']);
        
        if (isset($filters['date_from'])) {
            $query->where('enrollment_date', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('enrollment_date', '<=', $filters['date_to']);
        }
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['course_item_id'])) {
            $query->where('course_item_id', $filters['course_item_id']);
        }
        
        $enrollments = $query->get();
        
        // Thống kê theo trạng thái
        $statusStats = $enrollments->groupBy('status')->map->count();
        
        // Thống kê theo khóa học
        $courseStats = $enrollments->groupBy('course_item_id')->map(function($group) {
            $courseItem = $group->first()->courseItem;
            return [
                'name' => $courseItem->name,
                'count' => $group->count(),
                'total_fee' => $group->sum('final_fee')
            ];
        });
        
        return [
            'total_count' => $enrollments->count(),
            'total_fee' => $enrollments->sum('final_fee'),
            'status_stats' => $statusStats,
            'course_stats' => $courseStats,
            'enrollments' => $enrollments
        ];
    }

    public function getPaymentReport($filters = [])
    {
        $query = Payment::with(['enrollment.student', 'enrollment.courseItem']);
        
        if (isset($filters['date_from'])) {
            $query->where('payment_date', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('payment_date', '<=', $filters['date_to']);
        }
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }
        
        if (isset($filters['course_item_id'])) {
            $query->whereHas('enrollment', function($q) use ($filters) {
                $q->where('course_item_id', $filters['course_item_id']);
            });
        }
        
        $payments = $query->get();
        
        // Thống kê theo phương thức thanh toán
        $methodStats = $payments->groupBy('payment_method')->map(function($group) {
            return [
                'count' => $group->count(),
                'total_amount' => $group->sum('amount')
            ];
        });
        
        // Thống kê theo ngày
        $dateStats = $payments->groupBy(function($item) {
            return Carbon::parse($item->payment_date)->format('Y-m-d');
        })->map(function($group) {
            return [
                'count' => $group->count(),
                'total_amount' => $group->sum('amount')
            ];
        });
        
        // Thống kê theo khóa học
        $courseStats = $payments->groupBy('enrollment.course_item_id')->map(function($group) {
            $courseItem = $group->first()->enrollment->courseItem;
            return [
                'name' => $courseItem->name,
                'count' => $group->count(),
                'total_amount' => $group->sum('amount')
            ];
        });
        
        return [
            'total_count' => $payments->count(),
            'total_amount' => $payments->sum('amount'),
            'method_stats' => $methodStats,
            'date_stats' => $dateStats,
            'course_stats' => $courseStats,
            'payments' => $payments
        ];
    }

    public function getAttendanceReport($filters = [])
    {
        $query = Attendance::with(['student', 'courseItem']);
        
        if (isset($filters['date_from'])) {
            $query->where('attendance_date', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('attendance_date', '<=', $filters['date_to']);
        }
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['course_item_id'])) {
            $query->where('course_item_id', $filters['course_item_id']);
        }
        
        if (isset($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }
        
        $attendances = $query->get();
        
        // Thống kê theo trạng thái
        $statusStats = $attendances->groupBy('status')->map->count();
        
        // Thống kê theo khóa học
        $courseStats = $attendances->groupBy('course_item_id')->map(function($group) {
            $courseItem = $group->first()->courseItem;
            $total = $group->count();
            $present = $group->where('status', 'present')->count();
            
            return [
                'name' => $courseItem->name,
                'total' => $total,
                'present' => $present,
                'absent' => $group->where('status', 'absent')->count(),
                'late' => $group->where('status', 'late')->count(),
                'attendance_rate' => $total > 0 ? round(($present / $total) * 100) : 0
            ];
        });
        
        // Thống kê theo học viên
        $studentStats = $attendances->groupBy('student_id')->map(function($group) {
            $student = $group->first()->student;
            $total = $group->count();
            $present = $group->where('status', 'present')->count();
            
            return [
                'id' => $student->id,
                'name' => $student->full_name,
                'total' => $total,
                'present' => $present,
                'absent' => $group->where('status', 'absent')->count(),
                'late' => $group->where('status', 'late')->count(),
                'attendance_rate' => $total > 0 ? round(($present / $total) * 100) : 0
            ];
        });
        
        // Thống kê theo ngày
        $dateStats = $attendances->groupBy(function($item) {
            return Carbon::parse($item->attendance_date)->format('Y-m-d');
        })->map(function($group) {
            return [
                'date' => Carbon::parse($group->first()->attendance_date)->format('d/m/Y'),
                'total' => $group->count(),
                'present' => $group->where('status', 'present')->count(),
                'absent' => $group->where('status', 'absent')->count(),
                'late' => $group->where('status', 'late')->count()
            ];
        })->values();
        
        return [
            'total_count' => $attendances->count(),
            'present_count' => $attendances->where('status', 'present')->count(),
            'absent_count' => $attendances->where('status', 'absent')->count(),
            'late_count' => $attendances->where('status', 'late')->count(),
            'status_stats' => $statusStats,
            'course_stats' => $courseStats,
            'student_stats' => $studentStats,
            'attendances' => $attendances,
            'date_stats' => $dateStats
        ];
    }

    public function getRevenueReport($filters = [])
    {
        $query = Payment::where('status', 'confirmed');
        
        if (isset($filters['date_from'])) {
            $query->where('payment_date', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('payment_date', '<=', $filters['date_to']);
        }
        
        if (isset($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }
        
        $payments = $query->with(['enrollment.courseItem'])->get();
        
        // Thống kê theo tháng
        $monthlyStats = $payments->groupBy(function($item) {
            return Carbon::parse($item->payment_date)->format('Y-m');
        })->map(function($group) {
            return [
                'month' => Carbon::parse($group->first()->payment_date)->format('m/Y'),
                'count' => $group->count(),
                'amount' => $group->sum('amount')
            ];
        })->values();
        
        // Thống kê theo khóa học
        $courseStats = $payments->groupBy('enrollment.course_item_id')->map(function($group) {
            $courseItem = $group->first()->enrollment->courseItem;
            return [
                'name' => $courseItem->name,
                'count' => $group->count(),
                'amount' => $group->sum('amount')
            ];
        })->values();
        
        // Thống kê theo phương thức thanh toán
        $methodStats = $payments->groupBy('payment_method')->map(function($group) {
            return [
                'method' => $this->getPaymentMethodText($group->first()->payment_method),
                'count' => $group->count(),
                'amount' => $group->sum('amount')
            ];
        })->values();
        
        return [
            'total_amount' => $payments->sum('amount'),
            'total_count' => $payments->count(),
            'monthly_stats' => $monthlyStats,
            'course_stats' => $courseStats,
            'method_stats' => $methodStats
        ];
    }

    public function getDashboardSummary()
    {
        // Doanh thu tháng này
        $currentMonthRevenue = Payment::where('status', 'confirmed')
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount');
            
        // Doanh thu tháng trước
        $lastMonthRevenue = Payment::where('status', 'confirmed')
            ->whereMonth('payment_date', now()->subMonth()->month)
            ->whereYear('payment_date', now()->subMonth()->year)
            ->sum('amount');
        
        // Số học viên mới tháng này
        $newStudents = Student::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
            
        // Số khóa học có học viên đăng ký
        $activeCourses = CourseItem::whereHas('enrollments', function($query) {
                $query->where('status', \App\Enums\EnrollmentStatus::ACTIVE->value);
            })->count();
            
        // Số lượng ghi danh đang chờ thanh toán
        $pendingPayments = Enrollment::where('status', \App\Enums\EnrollmentStatus::ACTIVE->value)
            ->whereRaw('(select sum(amount) from payments where payments.enrollment_id = enrollments.id and payments.status = "confirmed") < enrollments.final_fee')
            ->orWhereDoesntHave('payments')
            ->count();
        
        return [
            'current_month_revenue' => $currentMonthRevenue,
            'last_month_revenue' => $lastMonthRevenue,
            'revenue_growth' => $lastMonthRevenue > 0 ? (($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0,
            'new_students' => $newStudents,
            'active_courses' => $activeCourses,
            'pending_payments' => $pendingPayments,
            'total_students' => Student::count(),
            'total_enrollments' => Enrollment::where('status', \App\Enums\EnrollmentStatus::ACTIVE->value)->count()
        ];
    }

    private function getPaymentMethodText($method)
    {
        switch ($method) {
            case 'cash':
                return 'Tiền mặt';
            case 'bank_transfer':
                return 'Chuyển khoản';
            case 'card':
                return 'Thẻ tín dụng';
            case 'qr_code':
                return 'Quét QR';
            case 'sepay':
                return 'SEPAY';
            default:
                return 'Không xác định';
        }
    }
} 
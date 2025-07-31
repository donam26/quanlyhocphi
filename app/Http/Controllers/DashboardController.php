<?php

namespace App\Http\Controllers;

use App\Models\CourseItem;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Thống kê tổng quan
        $stats = [
            'students_count' => Student::count(),
            'courses_count' => CourseItem::where('is_leaf', true)->where('active', true)->count(),
            'enrollments_count' => Enrollment::where('status', 'confirmed')->orWhere('status', 'active')->count(),
            'waitings_count' => Enrollment::where('status', 'waiting')->count(),
        ];

        // Thống kê tài chính
        $financialStats = [
            'total_fee' => Enrollment::whereIn('status', ['confirmed', 'active'])->sum('final_fee'),
            'total_paid' => Payment::where('status', 'confirmed')->sum('amount'),
            'total_pending' => Payment::where('status', 'pending')->sum('amount'),
            'recent_payments' => Payment::with(['enrollment.student', 'enrollment.courseItem'])
                                ->where('status', 'confirmed')
                                ->orderBy('payment_date', 'desc')
                                ->limit(5)
                                ->get(),
        ];
        $financialStats['total_remaining'] = max(0, $financialStats['total_fee'] - $financialStats['total_paid']);
        $financialStats['payment_rate'] = $financialStats['total_fee'] > 0 
            ? round(($financialStats['total_paid'] / $financialStats['total_fee']) * 100, 2) 
            : 0;

        // Thống kê học viên nợ học phí
        $unPaidCount = Enrollment::whereIn('status', ['confirmed', 'active'])
            ->whereRaw('(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payments.enrollment_id = enrollments.id AND payments.status = "confirmed") < enrollments.final_fee')
            ->count();

        // Thống kê ghi danh theo thời gian (6 tháng gần đây)
        $enrollmentChartData = $this->getEnrollmentChartData();
        
        // Thống kê thu nhập theo thời gian (6 tháng gần đây)
        $revenueChartData = $this->getRevenueChartData();
        
        // Thống kê thanh toán theo phương thức
        $paymentMethodData = $this->getPaymentMethodData();
        
     
        // Thống kê ghi danh theo khóa học (Top 5)
        $enrollmentsByCourse = $this->getEnrollmentsByCourse();
        
        // Thống kê doanh thu theo tuần gần đây nhất
        $revenueByDay = $this->getRevenueByDay();
       
        // Học viên mới nhất
        $newStudents = Student::orderBy('created_at', 'desc')->limit(5)->get();
        
        // Học viên chờ liên hệ
        $waitingContact = Enrollment::with('student', 'courseItem')
                            ->where('status', 'waiting')
                            ->where(function($query) {
                                $query->whereNull('last_status_change')
                                    ->orWhere('last_status_change', '<', now()->subDays(7));
                            })
                            ->orderBy('request_date', 'desc')
                            ->limit(5)
                            ->get();
        
        return view('dashboard.index', compact(
            'stats', 
            'financialStats', 
            'unPaidCount', 
            'enrollmentChartData', 
            'revenueChartData', 
            'paymentMethodData',
            'enrollmentsByCourse',
            'revenueByDay',
            'newStudents',
            'waitingContact'
        ));
    }
    
    private function getEnrollmentChartData()
    {
        $data = [];
        $labels = [];
        
        // Lấy dữ liệu 6 tháng gần đây
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $count = Enrollment::whereYear('enrollment_date', $month->year)
                    ->whereMonth('enrollment_date', $month->month)
                    ->whereIn('status', ['confirmed', 'active', 'completed'])
                    ->count();
            
            $labels[] = $month->format('M Y');
            $data[] = $count;
        }
        
        return [
            'labels' => $labels,
            'data' => $data
        ];
    }
    
    private function getRevenueChartData()
    {
        $data = [];
        $labels = [];
        
        // Lấy dữ liệu 6 tháng gần đây
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $revenue = Payment::whereYear('payment_date', $month->year)
                    ->whereMonth('payment_date', $month->month)
                    ->where('status', 'confirmed')
                    ->sum('amount');
            
            $labels[] = $month->format('M Y');
            $data[] = $revenue;
        }
        
        return [
            'labels' => $labels,
            'data' => $data
        ];
    }
    
    private function getPaymentMethodData()
    {
        $paymentMethods = Payment::where('status', 'confirmed')
            ->select('payment_method', DB::raw('SUM(amount) as total_amount'), DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get();
            
        $labels = [];
        $data = [];
        $backgroundColor = [];
        
        $colorMap = [
            'cash' => '#28a745', // green
            'bank_transfer' => '#007bff', // blue
            'card' => '#fd7e14', // orange
            'qr_code' => '#6f42c1', // purple
            'sepay' => '#e83e8c', // pink
            'other' => '#6c757d', // gray
        ];
        
        $methodLabels = [
            'cash' => 'Tiền mặt',
            'bank_transfer' => 'Chuyển khoản',
            'card' => 'Thẻ tín dụng',
            'qr_code' => 'Quét QR',
            'sepay' => 'SEPAY',
            'other' => 'Khác',
        ];
        
        foreach($paymentMethods as $method) {
            $methodName = $method->payment_method;
            $labels[] = $methodLabels[$methodName] ?? $methodName;
            $data[] = $method->total_amount;
            $backgroundColor[] = $colorMap[$methodName] ?? $colorMap['other'];
        }
        
        return [
            'labels' => $labels,
            'data' => $data,
            'backgroundColor' => $backgroundColor,
            'counts' => $paymentMethods
        ];
    }
    
    private function getStudentGenderData()
    {
        $genderData = Student::select('gender', DB::raw('count(*) as total'))
            ->groupBy('gender')
            ->get();
            
        $labels = [];
        $data = [];
        $backgroundColor = ['#007bff', '#e83e8c', '#6c757d']; // blue, pink, gray
        
        $genderLabels = [
            'male' => 'Nam',
            'female' => 'Nữ',
            'other' => 'Khác',
        ];
        
        foreach($genderData as $gender) {
            $genderName = $gender->gender;
            $labels[] = $genderLabels[$genderName] ?? 'Khác';
            $data[] = $gender->total;
        }
        
        return [
            'labels' => $labels,
            'data' => $data,
            'backgroundColor' => $backgroundColor
        ];
    }
    
    private function getPaymentStatusData()
    {
        // Đã thanh toán đủ
        $fullyPaid = Enrollment::whereIn('status', ['confirmed', 'active'])
            ->whereRaw('(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payments.enrollment_id = enrollments.id AND payments.status = "confirmed") >= enrollments.final_fee')
            ->count();
            
        // Đã thanh toán một phần
        $partiallyPaid = Enrollment::whereIn('status', ['confirmed', 'active'])
            ->whereRaw('(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payments.enrollment_id = enrollments.id AND payments.status = "confirmed") < enrollments.final_fee')
            ->whereRaw('(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payments.enrollment_id = enrollments.id AND payments.status = "confirmed") > 0')
            ->count();
            
        // Chưa thanh toán
        $notPaid = Enrollment::whereIn('status', ['confirmed', 'active'])
            ->whereRaw('(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payments.enrollment_id = enrollments.id AND payments.status = "confirmed") = 0')
            ->count();
            
        return [
            'labels' => ['Đã thanh toán đủ', 'Thanh toán một phần', 'Chưa thanh toán'],
            'data' => [$fullyPaid, $partiallyPaid, $notPaid],
            'backgroundColor' => ['#28a745', '#ffc107', '#dc3545'] // green, yellow, red
        ];
    }
    
    private function getEnrollmentsByCourse()
    {
        $enrollments = CourseItem::withCount(['enrollments' => function($query) {
                            $query->whereIn('status', ['confirmed', 'active']);
                        }])
                        ->where('is_leaf', true)
                        ->orderBy('enrollments_count', 'desc')
                        ->limit(10)
                        ->get();
                        
        $labels = $enrollments->pluck('name')->toArray();
        $data = $enrollments->pluck('enrollments_count')->toArray();
        
        return [
            'labels' => $labels,
            'data' => $data
        ];
    }
    
    private function getRevenueByDay()
    {
        $data = [];
        $labels = [];
        
        // Lấy dữ liệu 7 ngày gần đây
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::now()->subDays($i);
            $revenue = Payment::whereDate('payment_date', $day->toDateString())
                    ->where('status', 'confirmed')
                    ->sum('amount');
            
            $labels[] = $day->format('d/m');
            $data[] = $revenue;
        }
        
        return [
            'labels' => $labels,
            'data' => $data
        ];
    }
}

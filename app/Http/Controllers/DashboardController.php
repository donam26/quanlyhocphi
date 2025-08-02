<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index()
    {
        // Lấy thông tin tổng quan
        $summary = $this->dashboardService->getSummary();
        
        // Lấy hoạt động gần đây
        $recentActivities = $this->dashboardService->getRecentActivities(10);
        
        // Lấy lịch học sắp tới
        $upcomingSchedules = $this->dashboardService->getUpcomingSchedules(5);
        
        // Lấy biểu đồ doanh thu
        $revenueChartData = $this->dashboardService->getRevenueChart(6);
        
        // Lấy top khóa học
        $topCourses = $this->dashboardService->getTopCourses(10);
        
        // Lấy thông tin thanh toán chưa xử lý
        $pendingPayments = $this->dashboardService->getPendingPayments();
        
        // Định dạng dữ liệu biểu đồ doanh thu
        $formattedRevenueChart = [
            'labels' => collect($revenueChartData)->pluck('month')->toArray(),
            'data' => collect($revenueChartData)->pluck('revenue')->toArray()
        ];
        
        // Định dạng dữ liệu biểu đồ khóa học
        $enrollmentsByCourse = [
            'labels' => collect($topCourses)->pluck('name')->toArray(),
            'data' => collect($topCourses)->pluck('students_count')->toArray()
        ];

        return view('dashboard.index', [
            'stats' => [
                'students_count' => $summary['total_students'],
                'courses_count' => $summary['total_courses'],
                'enrollments_count' => $summary['active_enrollments'],
                'new_students' => $summary['new_students']
            ],
            'financialStats' => [
                'total_fee' => $summary['total_revenue'],
                'total_paid' => $summary['total_revenue'],
                'total_pending' => $pendingPayments['total_pending'],
                'payment_rate' => $summary['revenue_growth'],
                'recent_payments' => $recentActivities->filter(function($item) {
                    return $item['type'] === 'payment';
                })->take(5)
            ],
            'unPaidCount' => $pendingPayments['count'],
            'revenueChartData' => $formattedRevenueChart,
            'enrollmentsByCourse' => $enrollmentsByCourse,
            'newStudents' => collect($recentActivities)->filter(function($item) {
                return $item['type'] === 'enrollment';
            })->take(5),
            'waitingContact' => $pendingPayments['enrollments']
        ]);
    }
}

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

    public function index(Request $request)
    {
        // Xác định khoảng thời gian theo tham số, mặc định là theo ngày
        $timeRange = $request->get('time_range', 'day');
        
        // Kiểm tra tham số hợp lệ
        if (!in_array($timeRange, ['day', 'month', 'quarter', 'year', 'total'])) {
            $timeRange = 'day';
        }
        
        // Lấy tất cả dữ liệu dashboard theo khoảng thời gian
        $dashboardData = $this->dashboardService->getDashboardData($timeRange);
        
        return view('dashboard.index', [
            'timeRange' => $timeRange,
            'stats' => [
                'students_count' => $dashboardData['summary']['total_students'],
                'courses_count' => $dashboardData['summary']['total_courses'],
                'enrollments_count' => $dashboardData['summary']['active_enrollments'],
                'waitings_count' => $dashboardData['summary']['waitings_count'],
                'new_students' => $dashboardData['summary']['new_students']
            ],
            'financialStats' => [
                'total_revenue' => $dashboardData['summary']['total_revenue'],
                'current_period_revenue' => $dashboardData['revenue_by_time']['total'],
                'total_pending' => $dashboardData['pending_payments']['total_pending'],
                'payment_rate' => $dashboardData['summary']['revenue_growth'],
                'total_remaining' => $dashboardData['pending_payments']['total_pending']
            ],
            'revenueByTime' => $dashboardData['revenue_by_time'],
            'revenueByCourses' => $dashboardData['revenue_by_courses'],
            'studentsByCourses' => $dashboardData['students_by_courses'],
            'studentsByGender' => $dashboardData['students_by_gender'],
            'studentsByAge' => $dashboardData['students_by_age'],
            'studentsByLearningMode' => $dashboardData['students_by_learning_mode'],
            'studentsByRegion' => $dashboardData['students_by_region'],
            'waitingList' => $dashboardData['waiting_list'],
            'recentPayments' => $dashboardData['recent_payments'],
            'unPaidCount' => $dashboardData['pending_payments']['count'],
            'waitingEnrollments' => $dashboardData['pending_payments']['enrollments']
        ]);
    }
    
    /**
     * API lấy dữ liệu dashboard theo khoảng thời gian
     */
    public function getDataByTimeRange(Request $request)
    {
        // Xác định khoảng thời gian theo tham số
        $timeRange = $request->get('time_range', 'day');
        
        // Kiểm tra tham số hợp lệ
        if (!in_array($timeRange, ['day', 'month', 'quarter', 'year', 'total'])) {
            $timeRange = 'day';
        }
        
        // Nếu có courseItemId, lọc dữ liệu theo khóa học
        $courseItemId = $request->get('course_item_id');
        
        // Lấy dữ liệu theo loại yêu cầu
        $dataType = $request->get('data_type', 'revenue');
        $data = [];
        
        switch($dataType) {
            case 'revenue':
                $data = $this->dashboardService->getRevenueByTimeRange($timeRange);
                break;
                
            case 'revenue_by_courses':
                $data = $this->dashboardService->getRevenueByCoursesWithRatio($timeRange);
                break;
                
            case 'students_by_courses':
                $data = $this->dashboardService->getStudentsByCoursesWithRatio($timeRange);
                break;
                
            case 'students_by_gender':
                $data = $this->dashboardService->getStudentsByGender($timeRange);
                break;
                
            case 'students_by_age':
                $data = $this->dashboardService->getStudentsByAgeGroup($timeRange, $courseItemId);
                break;
                
            case 'students_by_learning_mode':
                $data = $this->dashboardService->getStudentsByLearningMode($timeRange, $courseItemId);
                break;
                
            case 'students_by_region':
                $data = $this->dashboardService->getStudentsByRegion($timeRange, $courseItemId);
                break;
                
            default:
                $data = $this->dashboardService->getRevenueByTimeRange($timeRange);
        }
        
        return response()->json($data);
    }
}

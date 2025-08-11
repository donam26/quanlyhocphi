<?php

namespace App\Http\Controllers;

use App\Models\CourseItem;
use App\Models\Student;
use App\Services\ReportService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Hiển thị trang chọn báo cáo
     */
    public function index()
    {
        $availableReports = [
            [
                'title' => 'Báo cáo doanh thu',
                'description' => 'Phân tích doanh thu theo thời gian, khóa học, phương thức thanh toán',
                'route' => 'reports.revenue',
                'icon' => 'fa-money-bill-wave',
                'color' => 'bg-success'
            ],
            [
                'title' => 'Báo cáo học viên',
                'description' => 'Thống kê học viên theo độ tuổi, giới tính, địa chỉ, trạng thái',
                'route' => 'reports.students',
                'icon' => 'fa-user-graduate',
                'color' => 'bg-primary'
            ],
            [
                'title' => 'Báo cáo ghi danh',
                'description' => 'Thống kê số lượng ghi danh theo thời gian, khóa học',
                'route' => 'reports.enrollments',
                'icon' => 'fa-clipboard-list',
                'color' => 'bg-info'
            ],
            [
                'title' => 'Báo cáo thanh toán',
                'description' => 'Thống kê thanh toán đầy đủ, thanh toán một phần và còn nợ',
                'route' => 'reports.payments',
                'icon' => 'fa-credit-card',
                'color' => 'bg-warning'
            ],
            [
                'title' => 'Báo cáo điểm danh',
                'description' => 'Thống kê điểm danh của học viên theo khóa học, thời gian',
                'route' => 'reports.attendance',
                'icon' => 'fa-clipboard-check',
                'color' => 'bg-danger'
            ],
        ];
        
        $dashboardSummary = $this->reportService->getDashboardSummary();
        $recentStats = [
            'revenue_today' => $dashboardSummary['today_revenue'] ?? 0,
            'revenue_month' => $dashboardSummary['current_month_revenue'],
            'new_students' => $dashboardSummary['new_students'],
            'new_enrollments' => $dashboardSummary['new_enrollments'] ?? 0,
        ];
        
        return view('reports.index', compact('availableReports', 'recentStats'));
    }
    
    /**
     * Báo cáo doanh thu
     */
    public function revenueReport(Request $request)
    {
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::now()->startOfMonth();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now()->endOfDay();
        
        $filters = [
            'date_from' => $startDate,
            'date_to' => $endDate,
            'payment_method' => $request->input('payment_method')
        ];
        
        $reportData = $this->reportService->getRevenueReport($filters);
        
        // Chuẩn bị dữ liệu cho biểu đồ
        $dailyRevenueData = [
            'labels' => $reportData['monthly_stats']->pluck('month')->toArray(),
            'values' => $reportData['monthly_stats']->pluck('amount')->toArray()
        ];
        
        // Dữ liệu cho biểu đồ phương thức thanh toán
        $methodLabels = $reportData['method_stats']->pluck('method')->toArray();
        $methodValues = $reportData['method_stats']->pluck('amount')->toArray();
        
        // Dữ liệu cho biểu đồ khóa học
        $courseLabels = $reportData['course_stats']->pluck('name')->toArray();
        $courseValues = $reportData['course_stats']->pluck('amount')->toArray();
        
        $courseItems = CourseItem::where('is_leaf', true)->where('active', true)->get();
        
        return view('reports.revenue', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalRevenue' => $reportData['total_amount'],
            'totalCount' => $reportData['total_count'],
            'dailyRevenueData' => $dailyRevenueData,
            'methodLabels' => $methodLabels,
            'methodValues' => $methodValues,
            'courseLabels' => $courseLabels,
            'courseValues' => $courseValues,
            'courseStats' => $reportData['course_stats'],
            'methodStats' => $reportData['method_stats'],
            'courseItems' => $courseItems,
            'revenueByMethod' => $reportData['method_stats'],
            'revenueByCourse' => $reportData['course_stats']
        ]);
    }
    
    /**
     * Báo cáo học viên
     */
    public function studentReport(Request $request)
    {
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : null;
        
        $filters = [
            'date_from' => $startDate,
            'date_to' => $endDate
        ];
        
        $reportData = $this->reportService->getStudentReport($filters);
        
        return view('reports.students', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalCount' => $reportData['total_count'],
            'activeCount' => $reportData['active_count'],
            'recentCount' => $reportData['recent_count'],
            'students' => $reportData['students'],
            'studentsByGender' => $reportData['gender_stats'] ?? collect(),
            'ageGroups' => $reportData['age_groups'] ?? [
                'under_18' => 0,
                '18_25' => 0,
                '26_35' => 0,
                '36_50' => 0,
                'above_50' => 0
            ]
        ]);
    }
    
    /**
     * Báo cáo ghi danh
     */
    public function enrollmentReport(Request $request)
    {
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : null;
        $courseItemId = $request->input('course_item_id');
        $status = $request->input('status');
        
        $filters = [
            'date_from' => $startDate,
            'date_to' => $endDate,
            'course_item_id' => $courseItemId,
            'status' => $status
        ];
        
        $reportData = $this->reportService->getEnrollmentReport($filters);
        
        $courseItems = CourseItem::where('is_leaf', true)->where('active', true)->get();
        
        // Đảm bảo enrollments không null
        if (!isset($reportData['enrollments'])) {
            $reportData['enrollments'] = collect();
        }
        
        return view('reports.enrollments', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedCourse' => $courseItemId,
            'selectedStatus' => $status,
            'totalCount' => $reportData['total_count'],
            'totalFee' => $reportData['total_fee'],
            'statusStats' => $reportData['status_stats'],
            'courseStats' => $reportData['course_stats'],
            'enrollments' => $reportData['enrollments'],
            'courseItems' => $courseItems
        ]);
    }
    
    /**
     * Báo cáo thanh toán
     */
    public function paymentReport(Request $request)
    {
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : null;
        $courseItemId = $request->input('course_item_id');
        $paymentMethod = $request->input('payment_method');
        $status = $request->input('status');
        
        $filters = [
            'date_from' => $startDate,
            'date_to' => $endDate,
            'course_item_id' => $courseItemId,
            'payment_method' => $paymentMethod,
            'status' => $status
        ];
        
        $reportData = $this->reportService->getPaymentReport($filters);
        
        $courseItems = CourseItem::where('is_leaf', true)->where('active', true)->get();
        
        // Đảm bảo payments không null
        if (!isset($reportData['payments'])) {
            $reportData['payments'] = collect();
        }
        
        return view('reports.payments', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedCourse' => $courseItemId,
            'selectedMethod' => $paymentMethod,
            'selectedStatus' => $status,
            'totalCount' => $reportData['total_count'],
            'totalAmount' => $reportData['total_amount'],
            'methodStats' => $reportData['method_stats'],
            'courseStats' => $reportData['course_stats'],
            'dateStats' => $reportData['date_stats'],
            'payments' => $reportData['payments'],
            'courseItems' => $courseItems
        ]);
    }
    
    /**
     * Báo cáo điểm danh
     */
    public function attendanceReport(Request $request)
    {
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : null;
        $courseItemId = $request->input('course_item_id');
        $studentId = $request->input('student_id');
        $status = $request->input('status');
        
        $filters = [
            'date_from' => $startDate,
            'date_to' => $endDate,
            'course_item_id' => $courseItemId,
            'student_id' => $studentId,
            'status' => $status
        ];
        
        $reportData = $this->reportService->getAttendanceReport($filters);
        
        $courseItems = CourseItem::where('is_leaf', true)->where('active', true)->get();
        $students = Student::orderBy('first_name')->orderBy('last_name')->get();
        
        // Lấy thông tin khóa học nếu có
        $courseItem = null;
        if ($courseItemId) {
            $courseItem = CourseItem::find($courseItemId);
        }
        
        // Dữ liệu cho biểu đồ
        $chartData = [];
        if (isset($reportData['date_stats']) && $reportData['date_stats']->count() > 0) {
            $chartData = [
                'labels' => $reportData['date_stats']->pluck('date')->toArray(),
                'present_data' => $reportData['date_stats']->pluck('present')->toArray(),
                'absent_data' => $reportData['date_stats']->pluck('absent')->toArray(),
                'late_data' => $reportData['date_stats']->pluck('late')->toArray()
            ];
        } else {
            $chartData = [
                'labels' => [],
                'present_data' => [],
                'absent_data' => [],
                'late_data' => []
            ];
        }
        
        // Thống kê trạng thái điểm danh
        $attendanceStats = [
            'present' => $reportData['present_count'] ?? 0,
            'absent' => $reportData['absent_count'] ?? 0,
            'late' => $reportData['late_count'] ?? 0
        ];
        
        return view('reports.attendance', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedCourse' => $courseItemId,
            'selectedStudent' => $studentId,
            'selectedStatus' => $status,
            'totalCount' => $reportData['total_count'],
            'presentCount' => $reportData['present_count'],
            'absentCount' => $reportData['absent_count'],
            'lateCount' => $reportData['late_count'],
            'statusStats' => $reportData['status_stats'],
            'courseStats' => $reportData['course_stats'],
            'studentStats' => $reportData['student_stats'],
            'attendances' => $reportData['attendances'],
            'courseItems' => $courseItems,
            'students' => $students,
            'courses' => $courseItems,
            'courseItem' => $courseItem,
            'chartData' => $chartData,
            'attendanceStats' => $attendanceStats
        ]);
    }
}

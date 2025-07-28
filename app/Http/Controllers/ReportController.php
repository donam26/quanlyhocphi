<?php

namespace App\Http\Controllers;

use App\Models\CourseItem;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Student;
use App\Models\WaitingList;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
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
        
        $recentStats = [
            'revenue_today' => Payment::where('status', 'confirmed')
                ->whereDate('payment_date', today())
                ->sum('amount'),
            'revenue_month' => Payment::where('status', 'confirmed')
                ->whereYear('payment_date', now()->year)
                ->whereMonth('payment_date', now()->month)
                ->sum('amount'),
            'new_students' => Student::whereDate('created_at', today())->count(),
            'new_enrollments' => Enrollment::whereDate('enrollment_date', today())->count(),
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
        
        // Tổng doanh thu trong khoảng thời gian
        $totalRevenue = Payment::where('status', 'confirmed')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->sum('amount');
            
        // Doanh thu theo ngày
        $dailyRevenue = Payment::where('status', 'confirmed')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->select(DB::raw('DATE(payment_date) as date'), DB::raw('SUM(amount) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
            
        // Doanh thu theo phương thức thanh toán
        $revenueByMethod = Payment::where('status', 'confirmed')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->select('payment_method', DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->orderBy('total', 'desc')
            ->get();
            
        // Doanh thu theo khóa học
        $revenueByCourse = Payment::where('payments.status', 'confirmed')
            ->whereBetween('payments.payment_date', [$startDate, $endDate])
            ->join('enrollments', 'payments.enrollment_id', '=', 'enrollments.id')
            ->join('course_items', 'enrollments.course_item_id', '=', 'course_items.id')
            ->select('course_items.name', DB::raw('SUM(payments.amount) as total'))
            ->groupBy('course_items.id', 'course_items.name')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get();
            
        // Dữ liệu biểu đồ
        $chartData = [
            'labels' => $dailyRevenue->pluck('date')->map(function($date) {
                return Carbon::parse($date)->format('d/m/Y');
            }),
            'data' => $dailyRevenue->pluck('total'),
        ];
        
        return view('reports.revenue', compact(
            'startDate', 
            'endDate', 
            'totalRevenue', 
            'dailyRevenue', 
            'revenueByMethod', 
            'revenueByCourse',
            'chartData'
        ));
    }
    
    /**
     * Báo cáo học viên
     */
    public function studentReport(Request $request)
    {
        // Thống kê số học viên theo giới tính
        $studentsByGender = Student::select('gender', DB::raw('count(*) as total'))
            ->groupBy('gender')
            ->get();
            
        // Học viên đang học, tạm dừng, đã hoàn thành
        $enrollmentCounts = Enrollment::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();
            
        // Học viên mới gần đây
        $recentStudents = Student::orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
            
        // Học viên có nhiều khóa học nhất
        $topStudents = Student::withCount('enrollments')
            ->orderBy('enrollments_count', 'desc')
            ->limit(10)
            ->get();
            
        // Độ tuổi học viên
        $ageGroups = [
            'under_18' => 0,
            '18_25' => 0,
            '26_35' => 0,
            '36_50' => 0,
            'above_50' => 0
        ];
        
        $students = Student::whereNotNull('date_of_birth')->get();
        foreach ($students as $student) {
            $age = Carbon::parse($student->date_of_birth)->age;
            if ($age < 18) {
                $ageGroups['under_18']++;
            } elseif ($age <= 25) {
                $ageGroups['18_25']++;
            } elseif ($age <= 35) {
                $ageGroups['26_35']++;
            } elseif ($age <= 50) {
                $ageGroups['36_50']++;
            } else {
                $ageGroups['above_50']++;
            }
        }
        
        return view('reports.students', compact(
            'studentsByGender',
            'enrollmentCounts',
            'recentStudents',
            'topStudents',
            'ageGroups'
        ));
    }
    
    /**
     * Báo cáo ghi danh
     */
    public function enrollmentReport(Request $request)
    {
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::now()->startOfMonth();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now()->endOfDay();
        
        // Tổng số ghi danh trong khoảng thời gian và theo trạng thái
        $totalEnrollments = [
            'total' => Enrollment::whereBetween('enrollment_date', [$startDate, $endDate])->count(),
            'enrolled' => Enrollment::whereBetween('enrollment_date', [$startDate, $endDate])
                ->where('status', 'enrolled')->count(),
            'completed' => Enrollment::whereBetween('enrollment_date', [$startDate, $endDate])
                ->where('status', 'completed')->count(),
            'cancelled' => Enrollment::whereBetween('enrollment_date', [$startDate, $endDate])
                ->where('status', 'cancelled')->count()
        ];
            
        // Ghi danh theo khóa học
        $enrollmentsByCourse = Enrollment::whereBetween('enrollment_date', [$startDate, $endDate])
            ->join('course_items', 'enrollments.course_item_id', '=', 'course_items.id')
            ->select('course_items.name', DB::raw('count(*) as count'))
            ->groupBy('course_items.id', 'course_items.name')
            ->orderBy('count', 'desc')
            ->get();
            
        // Ghi danh theo ngày
        $dailyEnrollments = Enrollment::whereBetween('enrollment_date', [$startDate, $endDate])
            ->select(DB::raw('DATE(enrollment_date) as date'), DB::raw('COUNT(*) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
            
        // Ghi danh theo trạng thái
        $enrollmentsByStatus = Enrollment::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();
            
        // Khóa học nổi bật (số lượng ghi danh nhiều nhất)
        $topCourses = CourseItem::withCount(['enrollments'])
            ->where('is_leaf', true)
            ->orderBy('enrollments_count', 'desc')
            ->limit(5)
            ->get();
            
        // Tỷ lệ hoàn thành học phí
        $paymentStats = [
            'paid' => Enrollment::where('status', 'enrolled')
                ->whereRaw('(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payments.enrollment_id = enrollments.id AND payments.status = "confirmed") >= enrollments.final_fee')
                ->count(),
            'partially_paid' => Enrollment::where('status', 'enrolled')
                ->whereRaw('(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payments.enrollment_id = enrollments.id AND payments.status = "confirmed") < enrollments.final_fee')
                ->whereRaw('(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payments.enrollment_id = enrollments.id AND payments.status = "confirmed") > 0')
                ->count(),
            'not_paid' => Enrollment::where('status', 'enrolled')
                ->whereRaw('(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payments.enrollment_id = enrollments.id AND payments.status = "confirmed") = 0')
                ->count(),
        ];
            
        // Dữ liệu biểu đồ
        $chartData = [
            'labels' => $dailyEnrollments->pluck('date')->map(function($date) {
                return Carbon::parse($date)->format('d/m/Y');
            }),
            'data' => $dailyEnrollments->pluck('total'),
        ];
        
        return view('reports.enrollments', compact(
            'startDate',
            'endDate',
            'totalEnrollments',
            'enrollmentsByCourse',
            'dailyEnrollments',
            'enrollmentsByStatus',
            'topCourses',
            'paymentStats',
            'chartData'
        ));
    }
    
    /**
     * Báo cáo thanh toán
     */
    public function paymentReport(Request $request)
    {
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::now()->startOfMonth();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now()->endOfDay();
        
        // Thống kê tổng quát
        $totalStats = [
            'total_revenue' => Payment::where('status', 'confirmed')
                ->whereBetween('payment_date', [$startDate, $endDate])
                ->sum('amount'),
            'pending_payments' => Payment::where('status', 'pending')
                ->whereBetween('payment_date', [$startDate, $endDate])
                ->sum('amount'),
            'rejected_payments' => Payment::where('status', 'rejected')
                ->whereBetween('payment_date', [$startDate, $endDate])
                ->sum('amount'),
            'payment_count' => Payment::where('status', 'confirmed')
                ->whereBetween('payment_date', [$startDate, $endDate])
                ->count(),
        ];
        
        // Thanh toán theo phương thức
        $paymentsByMethod = Payment::where('status', 'confirmed')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->get();
            
        // Thanh toán theo trạng thái
        $paymentsByStatus = Payment::whereBetween('payment_date', [$startDate, $endDate])
            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('status')
            ->get();
            
        // Thanh toán theo ngày
        $dailyPayments = Payment::where('status', 'confirmed')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->select(DB::raw('DATE(payment_date) as date'), DB::raw('SUM(amount) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
            
        // Danh sách thanh toán gần đây
        $recentPayments = Payment::with(['enrollment', 'enrollment.student', 'enrollment.courseItem'])
            ->where('status', 'confirmed')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->orderBy('payment_date', 'desc')
            ->limit(10)
            ->get();
            
        // Công nợ phân loại
        $debtStats = [
            'total_fee' => Enrollment::where('status', 'enrolled')->sum('final_fee'),
            'total_paid' => Payment::where('status', 'confirmed')->sum('amount'),
        ];
        $debtStats['total_debt'] = max(0, $debtStats['total_fee'] - $debtStats['total_paid']);
        $debtStats['payment_rate'] = $debtStats['total_fee'] > 0 
            ? round(($debtStats['total_paid'] / $debtStats['total_fee']) * 100, 2) 
            : 0;
            
        // Dữ liệu biểu đồ
        $chartData = [
            'labels' => $dailyPayments->pluck('date')->map(function($date) {
                return Carbon::parse($date)->format('d/m/Y');
            }),
            'data' => $dailyPayments->pluck('total'),
        ];
        
        return view('reports.payments', compact(
            'startDate',
            'endDate',
            'totalStats',
            'paymentsByMethod',
            'paymentsByStatus',
            'dailyPayments',
            'recentPayments',
            'debtStats',
            'chartData'
        ));
    }

    /**
     * Báo cáo điểm danh
     */
    public function attendanceReport(Request $request)
    {
        $courseItem = null;
        $attendanceStats = [];
        $attendanceByDate = collect();
        $studentsAttendance = collect();
        $chartData = null;
        
        if ($request->has('course_item_id')) {
            $courseItem = CourseItem::findOrFail($request->course_item_id);
            
            // Lấy thống kê điểm danh cho khóa học này
            $attendanceStats = Attendance::where('course_item_id', $request->course_item_id)
                ->select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status')
                ->toArray();
                
            // Đảm bảo có đủ các trạng thái mặc định nếu không có dữ liệu
            if (!isset($attendanceStats['present'])) $attendanceStats['present'] = 0;
            if (!isset($attendanceStats['absent'])) $attendanceStats['absent'] = 0;
            if (!isset($attendanceStats['late'])) $attendanceStats['late'] = 0;
                
            // Lấy thống kê điểm danh theo ngày
            $attendanceByDate = Attendance::where('course_item_id', $request->course_item_id)
                ->select(DB::raw('DATE(attendance_date) as date'), 
                         DB::raw('COUNT(CASE WHEN status = "present" THEN 1 END) as present_count'),
                         DB::raw('COUNT(CASE WHEN status = "absent" THEN 1 END) as absent_count'),
                         DB::raw('COUNT(CASE WHEN status = "late" THEN 1 END) as late_count'))
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->get();
                
            // Học viên với tỷ lệ điểm danh cao nhất
            $studentsAttendance = DB::table('attendances')
                ->join('students', 'attendances.student_id', '=', 'students.id')
                ->where('attendances.course_item_id', $request->course_item_id)
                ->select(
                    'students.id',
                    'students.full_name',
                    DB::raw('COUNT(*) as total'),
                    DB::raw('SUM(CASE WHEN attendances.status = "present" THEN 1 ELSE 0 END) as present_count'),
                    DB::raw('SUM(CASE WHEN attendances.status = "absent" THEN 1 ELSE 0 END) as absent_count'),
                    DB::raw('SUM(CASE WHEN attendances.status = "late" THEN 1 ELSE 0 END) as late_count')
                )
                ->groupBy('students.id', 'students.full_name')
                ->orderByRaw('SUM(CASE WHEN attendances.status = "present" THEN 1 ELSE 0 END) DESC, SUM(CASE WHEN attendances.status = "late" THEN 1 ELSE 0 END) ASC')
                ->get();
                
            foreach ($studentsAttendance as $student) {
                $student->attendance_rate = $student->total > 0 
                    ? round((($student->present_count + $student->late_count * 0.5) / $student->total) * 100, 1) 
                    : 0;
            }
            
            $chartData = [
                'labels' => $attendanceByDate->pluck('date')->map(function($date) {
                    return Carbon::parse($date)->format('d/m/Y');
                }),
                'present_data' => $attendanceByDate->pluck('present_count'),
                'absent_data' => $attendanceByDate->pluck('absent_count'),
                'late_data' => $attendanceByDate->pluck('late_count'),
            ];
        }
        
        // Lấy danh sách khóa học có điểm danh
        $courses = CourseItem::where('is_leaf', true)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('attendances')
                      ->whereColumn('attendances.course_item_id', 'course_items.id');
            })
            ->orderBy('name')
            ->get();
        
        return view('reports.attendance', compact(
            'courses',
            'courseItem',
            'attendanceStats',
            'attendanceByDate',
            'studentsAttendance',
            'chartData'
        ));
    }
    
    /**
     * Export báo cáo doanh thu
     */
    public function exportRevenueReport(Request $request)
    {
        // Thêm code xuất Excel báo cáo doanh thu
    }
    
    /**
     * Export báo cáo thanh toán
     */
    public function exportPaymentReport(Request $request)
    {
        // Thêm code xuất Excel báo cáo thanh toán
    }
    
    /**
     * Export báo cáo điểm danh
     */
    public function exportAttendanceReport(Request $request)
    {
        // Thêm code xuất Excel báo cáo điểm danh
    }
}

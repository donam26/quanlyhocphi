<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseClass;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Hiển thị trang báo cáo chính
     */
    public function index()
    {
        // Thống kê cơ bản
        $totalStudents = Student::count();
        $totalClasses = CourseClass::count();
        $activeClasses = CourseClass::where('status', 'open')->count();
        $completedClasses = CourseClass::where('status', 'completed')->count();
        $totalRevenue = Payment::where('status', 'confirmed')->sum('amount');
        
        // Tính tổng số tiền còn nợ
        $totalFees = Enrollment::sum('final_fee');
        $totalUnpaid = $totalFees - $totalRevenue;
        
        // Thống kê học viên chưa thanh toán
        $unpaidCount = Enrollment::whereDoesntHave('payments', function ($q) {
            $q->where('status', 'confirmed');
        })->orWhereHas('payments', function ($q) {
            $q->select('enrollment_id', DB::raw('SUM(amount) as total_paid'))
              ->where('status', 'confirmed')
              ->groupBy('enrollment_id')
              ->havingRaw('SUM(amount) = 0');
        })->count();
        
        // Thống kê học viên thanh toán một phần
        $partialPaidCount = Enrollment::whereHas('payments', function ($q) {
            $q->select('enrollment_id', DB::raw('SUM(amount) as total_paid'))
              ->where('status', 'confirmed')
              ->groupBy('enrollment_id')
              ->havingRaw('SUM(amount) > 0')
              ->havingRaw('SUM(amount) < enrollments.final_fee');
        })->count();
        
        // Tính công nợ trung bình mỗi học viên
        $averageDebt = ($unpaidCount + $partialPaidCount) > 0 ? 
            $totalUnpaid / ($unpaidCount + $partialPaidCount) : 0;
        
        // Tính tỷ lệ lấp đầy trung bình
        $averageOccupancy = CourseClass::where('max_students', '>', 0)
            ->whereIn('status', ['open', 'in_progress'])
            ->get()
            ->map(function($class) {
                $enrolledCount = $class->enrollments()->where('status', 'enrolled')->count();
                return $class->max_students > 0 ? ($enrolledCount / $class->max_students) * 100 : 0;
            })
            ->avg() ?? 0;
        
        // Dữ liệu biểu đồ doanh thu theo tháng
        $revenueData = $this->getMonthlyRevenue();
        
        // Dữ liệu biểu đồ phân bổ học viên theo khóa học
        $courseDistribution = $this->getCourseDistribution();
        $distributionLabels = $courseDistribution->pluck('name')->toArray();
        $distributionData = $courseDistribution->pluck('count')->toArray();
        
        return view('reports.index', compact(
            'totalStudents', 
            'totalClasses',
            'activeClasses',
            'completedClasses',
            'totalRevenue',
            'totalUnpaid',
            'unpaidCount',
            'partialPaidCount',
            'averageDebt',
            'averageOccupancy',
            'revenueData',
            'distributionLabels',
            'distributionData'
        ));
    }
    
    /**
     * Lấy dữ liệu doanh thu theo tháng
     */
    private function getMonthlyRevenue()
    {
        $currentYear = Carbon::now()->year;
        
        $monthlyRevenue = Payment::where('status', 'confirmed')
            ->whereYear('payment_date', $currentYear)
            ->select(DB::raw('MONTH(payment_date) as month'), DB::raw('SUM(amount) as total'))
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month')
            ->toArray();
        
        // Điền giá trị 0 cho các tháng không có doanh thu
        $revenueData = [];
        for ($i = 1; $i <= 12; $i++) {
            $revenueData[$i-1] = $monthlyRevenue[$i] ?? 0;
        }
        
        return $revenueData;
    }
    
    /**
     * Lấy dữ liệu phân bổ học viên theo khóa học
     */
    private function getCourseDistribution()
    {
        return Course::withCount(['enrollments' => function($query) {
                $query->where('status', 'enrolled');
            }])
            ->orderByDesc('enrollments_count')
            ->take(5)
            ->get()
            ->map(function($course) {
                return [
                    'name' => $course->name,
                    'count' => $course->enrollments_count
                ];
            });
    }
}

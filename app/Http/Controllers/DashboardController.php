<?php

namespace App\Http\Controllers;

use App\Models\CourseItem;
use App\Models\Classes;
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
            'total_students' => Student::count(),
            'total_courses' => CourseItem::where('is_leaf', true)->count(),
            'total_classes' => Classes::count(),
            'total_enrollments' => Enrollment::count(),
            'unpaid_count' => Enrollment::whereHas('payments', function ($query) {
                $query->where('status', 'pending');
            })->count(),
        ];

        // Tổng doanh thu
        $totalRevenue = Payment::where('status', 'confirmed')->sum('amount');
        
        // Doanh thu theo tháng trong năm hiện tại
        $monthlyRevenue = Payment::where('status', 'confirmed')
            ->whereYear('payment_date', Carbon::now()->year)
            ->select(DB::raw('MONTH(payment_date) as month'), DB::raw('SUM(amount) as total'))
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month')
            ->toArray();

        // Điền giá trị 0 cho các tháng không có doanh thu
        $chartData = [];
        for ($i = 1; $i <= 12; $i++) {
            $chartData[$i] = $monthlyRevenue[$i] ?? 0;
        }

        // Lấy danh sách học viên chưa thanh toán gần đây
        $unpaidStudents = Enrollment::whereHas('payments', function ($query) {
            $query->where('status', 'pending');
        })->with(['student', 'class'])
            ->latest()
            ->take(5)
            ->get();

        // Lấy danh sách thanh toán gần đây
        $recentPayments = Payment::where('status', 'confirmed')
            ->with(['enrollment.student', 'enrollment.class'])
            ->latest('payment_date')
            ->take(5)
            ->get();

        return view('dashboard.index', compact(
            'stats',
            'totalRevenue',
            'chartData',
            'unpaidStudents',
            'recentPayments'
        ));
    }
}

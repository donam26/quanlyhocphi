<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseItem;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Attendance;
use App\Services\ReportService;
use App\Enums\EnrollmentStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * API: Báo cáo doanh thu
     */
    public function revenue(Request $request)
    {
        try {
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::now()->startOfMonth();
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now()->endOfDay();
            
            $filters = [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'course_item_id' => $request->input('course_item_id'),
                'payment_method' => $request->input('payment_method'),
            ];

            $data = $this->reportService->getRevenueReport($filters);
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'filters' => $filters
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo báo cáo doanh thu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Báo cáo học viên
     */
    public function students(Request $request)
    {
        try {
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : null;
            
            $filters = [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'province_id' => $request->input('province_id'),
                'gender' => $request->input('gender'),
                'education_level' => $request->input('education_level'),
            ];

            $data = $this->reportService->getStudentReport($filters);
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'filters' => $filters
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo báo cáo học viên: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Báo cáo ghi danh
     */
    public function enrollments(Request $request)
    {
        try {
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : null;
            
            $filters = [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'course_item_id' => $request->input('course_item_id'),
                'status' => $request->input('status'),
            ];

            $data = $this->reportService->getEnrollmentReport($filters);
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'filters' => $filters
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo báo cáo ghi danh: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Báo cáo thanh toán
     */
    public function payments(Request $request)
    {
        try {
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : null;
            
            $filters = [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'course_item_id' => $request->input('course_item_id'),
                'payment_method' => $request->input('payment_method'),
                'status' => $request->input('status'),
            ];

            $data = $this->reportService->getPaymentReport($filters);
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'filters' => $filters
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo báo cáo thanh toán: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Báo cáo điểm danh
     */
    public function attendance(Request $request)
    {
        try {
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : null;
            
            $filters = [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'course_item_id' => $request->input('course_item_id'),
                'student_id' => $request->input('student_id'),
                'status' => $request->input('status'),
            ];

            $data = $this->reportService->getAttendanceReport($filters);
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'filters' => $filters
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo báo cáo điểm danh: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Báo cáo hoàn thành khóa học
     */
    public function courseCompletion(Request $request)
    {
        try {
            $courseItemId = $request->input('course_item_id');
            
            $query = Enrollment::with(['student', 'courseItem'])
                ->where('status', EnrollmentStatus::COMPLETED);
                
            if ($courseItemId) {
                $query->where('course_item_id', $courseItemId);
            }
            
            $completedEnrollments = $query->get();
            
            $data = [
                'total_completed' => $completedEnrollments->count(),
                'by_course' => $completedEnrollments->groupBy('courseItem.name')->map(function ($group) {
                    return $group->count();
                }),
                'recent_completions' => $completedEnrollments->sortByDesc('updated_at')->take(10)->values()
            ];
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo báo cáo hoàn thành khóa học: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Xu hướng doanh thu
     */
    public function revenueTrend(Request $request)
    {
        try {
            $months = $request->input('months', 12);
            $startDate = Carbon::now()->subMonths($months)->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
            
            $revenueByMonth = Payment::where('status', 'confirmed')
                ->whereBetween('payment_date', [$startDate, $endDate])
                ->selectRaw('YEAR(payment_date) as year, MONTH(payment_date) as month, SUM(amount) as total')
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $revenueByMonth
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo báo cáo xu hướng doanh thu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Xu hướng ghi danh
     */
    public function enrollmentTrend(Request $request)
    {
        try {
            $months = $request->input('months', 12);
            $startDate = Carbon::now()->subMonths($months)->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();

            $enrollmentsByMonth = Enrollment::whereBetween('enrollment_date', [$startDate, $endDate])
                ->selectRaw('YEAR(enrollment_date) as year, MONTH(enrollment_date) as month, COUNT(*) as total')
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $enrollmentsByMonth
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo báo cáo xu hướng ghi danh: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Thống kê danh sách chờ
     */
    public function waitingListStats(Request $request)
    {
        try {
            $waitingEnrollments = Enrollment::where('status', EnrollmentStatus::WAITING)
                ->with(['student', 'courseItem'])
                ->get();

            $data = [
                'total_waiting' => $waitingEnrollments->count(),
                'by_course' => $waitingEnrollments->groupBy('courseItem.name')->map(function ($group) {
                    return [
                        'count' => $group->count(),
                        'students' => $group->pluck('student.full_name')
                    ];
                }),
                'recent_additions' => $waitingEnrollments->sortByDesc('created_at')->take(10)->values()
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo thống kê danh sách chờ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Thanh toán quá hạn
     */
    public function overduePayments(Request $request)
    {
        try {
            $overdueDate = Carbon::now()->subDays(30); // Quá hạn 30 ngày

            $overdueEnrollments = Enrollment::where('status', 'active')
                ->where('enrollment_date', '<', $overdueDate)
                ->with(['student', 'courseItem', 'payments'])
                ->get()
                ->filter(function ($enrollment) {
                    $totalPaid = $enrollment->payments->where('status', 'confirmed')->sum('amount');
                    return $totalPaid < $enrollment->final_fee;
                });

            $data = [
                'total_overdue' => $overdueEnrollments->count(),
                'total_amount_due' => $overdueEnrollments->sum(function ($enrollment) {
                    $totalPaid = $enrollment->payments->where('status', 'confirmed')->sum('amount');
                    return $enrollment->final_fee - $totalPaid;
                }),
                'enrollments' => $overdueEnrollments->values()
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo báo cáo thanh toán quá hạn: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Phân bố khóa học
     */
    public function courseDistribution(Request $request)
    {
        try {
            $enrollmentsByCourse = Enrollment::with('courseItem')
                ->selectRaw('course_item_id, COUNT(*) as total_enrollments, SUM(final_fee) as total_revenue')
                ->groupBy('course_item_id')
                ->get();

            $data = $enrollmentsByCourse->map(function ($item) {
                return [
                    'course_name' => $item->courseItem->name ?? 'Unknown',
                    'total_enrollments' => $item->total_enrollments,
                    'total_revenue' => $item->total_revenue,
                    'average_fee' => $item->total_enrollments > 0 ? $item->total_revenue / $item->total_enrollments : 0
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo báo cáo phân bố khóa học: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Thống kê nhân khẩu học viên
     */
    public function studentDemographics(Request $request)
    {
        try {
            $students = Student::all();

            $data = [
                'total_students' => $students->count(),
                'by_gender' => $students->groupBy('gender')->map(function ($group) {
                    return $group->count();
                }),
                'by_education_level' => $students->groupBy('education_level')->map(function ($group) {
                    return $group->count();
                }),
                'by_age_group' => $students->groupBy(function ($student) {
                    $age = Carbon::parse($student->date_of_birth)->age;
                    if ($age < 25) return 'Under 25';
                    if ($age < 35) return '25-34';
                    if ($age < 45) return '35-44';
                    if ($age < 55) return '45-54';
                    return '55+';
                })->map(function ($group) {
                    return $group->count();
                })
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo thống kê nhân khẩu học viên: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Top khóa học phổ biến
     */
    public function topCourses(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);

            $topCourses = Enrollment::with('courseItem')
                ->selectRaw('course_item_id, COUNT(*) as total_enrollments, SUM(final_fee) as total_revenue')
                ->groupBy('course_item_id')
                ->orderByDesc('total_enrollments')
                ->limit($limit)
                ->get()
                ->map(function ($item) {
                    return [
                        'course_name' => $item->courseItem->name ?? 'Unknown',
                        'total_enrollments' => $item->total_enrollments,
                        'total_revenue' => $item->total_revenue,
                        'average_fee' => $item->total_enrollments > 0 ? $item->total_revenue / $item->total_enrollments : 0
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $topCourses
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo báo cáo top khóa học: ' . $e->getMessage()
            ], 500);
        }
    }
}

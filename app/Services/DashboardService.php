<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\CourseItem;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Student;
use App\Enums\EnrollmentStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Lấy dữ liệu tổng quan cho dashboard
     */
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
        $activeEnrollments = Enrollment::where('status', EnrollmentStatus::ACTIVE)->count();
        
        // Tổng số ghi danh đang chờ
        $waitingsCount = Enrollment::where('status', EnrollmentStatus::WAITING)->count();
        
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
            'waitings_count' => $waitingsCount,
            'total_revenue' => $totalRevenue,
            'current_month_revenue' => $currentMonthRevenue,
            'revenue_growth' => $revenueGrowth
        ];
    }

    /**
     * Lấy doanh thu theo thời gian (ngày, tháng, quý, năm)
     */
    public function getRevenueByTimeRange($range = 'day', $limit = 7)
    {
        $today = now();
        $query = Payment::select(
                DB::raw('SUM(amount) as revenue')
            )
            ->where('status', 'confirmed');
        
        $labels = [];
        $groupBy = '';
        
        switch($range) {
            case 'day':
                // Ngày trong tuần gần đây
                $startDate = $today->copy()->subDays($limit - 1)->startOfDay();
                $query->whereDate('payment_date', '>=', $startDate);
                $query->addSelect(DB::raw('DATE(payment_date) as date_group'));
                $groupBy = 'date_group';
                
                // Tạo labels cho các ngày
                for ($i = $limit - 1; $i >= 0; $i--) {
                    $date = $today->copy()->subDays($i);
                    $labels[$date->format('Y-m-d')] = $date->format('d/m');
                }
                break;
                
            case 'month':
                // Các tháng gần đây
                $startDate = $today->copy()->subMonths($limit - 1)->startOfMonth();
                $query->where('payment_date', '>=', $startDate);
                $query->addSelect(DB::raw('DATE_FORMAT(payment_date, "%Y-%m") as date_group'));
                $groupBy = 'date_group';
                
                // Tạo labels cho các tháng
                for ($i = $limit - 1; $i >= 0; $i--) {
                    $date = $today->copy()->subMonths($i);
                    $labels[$date->format('Y-m')] = $date->format('m/Y');
                }
                break;
                
            case 'quarter':
                // Các quý gần đây
                $startDate = $today->copy()->subQuarters($limit - 1)->startOfQuarter();
                $query->where('payment_date', '>=', $startDate);
                $query->addSelect(DB::raw('CONCAT(YEAR(payment_date), "-Q", QUARTER(payment_date)) as date_group'));
                $groupBy = 'date_group';
                
                // Tạo labels cho các quý
                for ($i = $limit - 1; $i >= 0; $i--) {
                    $date = $today->copy()->subQuarters($i);
                    $labels[$date->year . '-Q' . $date->quarter] = 'Q' . $date->quarter . '/' . $date->year;
                }
                break;
                
            case 'year':
                // Các năm gần đây
                $startDate = $today->copy()->subYears($limit - 1)->startOfYear();
                $query->where('payment_date', '>=', $startDate);
                $query->addSelect(DB::raw('YEAR(payment_date) as date_group'));
                $groupBy = 'date_group';
                
                // Tạo labels cho các năm
                for ($i = $limit - 1; $i >= 0; $i--) {
                    $year = $today->copy()->subYears($i)->year;
                    $labels[$year] = (string)$year;
                }
                break;
        }
        
        // Thực hiện truy vấn
        $results = $query->groupBy($groupBy)->orderBy($groupBy)->get();
        
        // Khởi tạo mảng dữ liệu
        $data = array_fill_keys(array_keys($labels), 0);
        
        // Đổ dữ liệu vào mảng
        foreach ($results as $result) {
            $data[$result->date_group] = (float) $result->revenue;
        }
        
        // Tính tổng doanh thu cho giai đoạn
        $totalRevenue = array_sum($data);
        
        return [
            'data' => collect($labels)->map(function($label, $key) use ($data) {
                return [
                    'label' => $label,
                    'value' => $data[$key] ?? 0
                ];
            })->values()->toArray(),
            'total' => $totalRevenue
        ];
    }
    
    /**
     * Lấy doanh thu và tỉ trọng theo khóa học
     */
    public function getRevenueByCoursesWithRatio($range = 'day', $limit = 10)
    {
        $today = now();
        $startDate = null;
        
        switch($range) {
            case 'day':
                $startDate = $today->copy()->startOfDay();
                break;
            case 'month':
                $startDate = $today->copy()->startOfMonth();
                break;
            case 'quarter':
                $startDate = $today->copy()->startOfQuarter();
                break;
            case 'year':
                $startDate = $today->copy()->startOfYear();
                break;
            default:
                $startDate = $today->copy()->startOfDay();
        }
        
        // Lấy dữ liệu doanh thu theo khóa học
        $courses = CourseItem::withCount(['enrollments as revenue' => function ($query) use ($startDate) {
                $query->select(DB::raw('COALESCE(SUM(p.amount), 0)'))
                    ->join('payments as p', 'enrollments.id', '=', 'p.enrollment_id')
                    ->where('p.status', 'confirmed')
                    ->where('p.payment_date', '>=', $startDate);
            }])
            ->having('revenue', '>', 0)
            ->orderBy('revenue', 'desc')
            ->take($limit)
            ->get();
        
        // Tính tổng doanh thu của tất cả khóa học trong khoảng thời gian
        $totalRevenue = Payment::where('status', 'confirmed')
            ->whereDate('payment_date', '>=', $startDate)
            ->sum('amount');
            
        $courseData = $courses->map(function($course) use ($totalRevenue) {
            $ratio = $totalRevenue > 0 ? round(($course->revenue / $totalRevenue) * 100, 2) : 0;
            return [
                'id' => $course->id,
                'name' => $course->name,
                'revenue' => $course->revenue,
                'ratio' => $ratio
            ];
        });
        
        return [
            'data' => $courseData->toArray(),
            'total' => $totalRevenue
        ];
    }
    
    /**
     * Lấy số lượng học viên và tỉ trọng theo khóa học
     */
    public function getStudentsByCoursesWithRatio($range = 'day', $limit = 10) 
    {
        $today = now();
        $startDate = null;
        
        switch($range) {
            case 'day':
                $startDate = $today->copy()->startOfDay();
                break;
            case 'month':
                $startDate = $today->copy()->startOfMonth();
                break;
            case 'quarter':
                $startDate = $today->copy()->startOfQuarter();
                break;
            case 'year':
                $startDate = $today->copy()->startOfYear();
                break;
            default:
                $startDate = $today->copy()->startOfDay();
        }
        
        // Lấy dữ liệu số học viên theo khóa học
        $courses = CourseItem::withCount(['enrollments as students_count' => function ($query) use ($startDate) {
                $query->where('status', EnrollmentStatus::ACTIVE)
                    ->where('enrollment_date', '>=', $startDate);
            }])
            ->having('students_count', '>', 0)
            ->orderBy('students_count', 'desc')
            ->take($limit)
            ->get();
        
        // Tính tổng số học viên trong khoảng thời gian
        $totalStudents = Enrollment::where('status', EnrollmentStatus::ACTIVE)
            ->whereDate('enrollment_date', '>=', $startDate)
            ->count();
            
        $courseData = $courses->map(function($course) use ($totalStudents) {
            $ratio = $totalStudents > 0 ? round(($course->students_count / $totalStudents) * 100, 2) : 0;
            return [
                'id' => $course->id,
                'name' => $course->name,
                'students_count' => $course->students_count,
                'ratio' => $ratio
            ];
        });
        
        return [
            'labels' => $courseData->pluck('name')->toArray(),
            'data' => $courseData->pluck('students_count')->toArray(),
            'ratio' => $courseData->pluck('ratio')->toArray(),
            'total' => $totalStudents
        ];
    }

    /**
     * Lấy thống kê học viên theo giới tính
     */
    public function getStudentsByGender($range = 'total')
    {
        $query = Student::select('gender', DB::raw('count(*) as count'));
        
        // Lọc theo khoảng thời gian nếu cần
        if ($range !== 'total') {
            $today = now();
            
            switch($range) {
                case 'day':
                    $query->whereDate('created_at', $today);
                    break;
                case 'month':
                    $query->whereYear('created_at', $today->year)
                        ->whereMonth('created_at', $today->month);
                    break;
                case 'quarter':
                    $startQuarter = $today->copy()->startOfQuarter();
                    $endQuarter = $today->copy()->endOfQuarter();
                    $query->whereBetween('created_at', [$startQuarter, $endQuarter]);
                    break;
                case 'year':
                    $query->whereYear('created_at', $today->year);
                    break;
            }
        }
        
        $results = $query->groupBy('gender')->get();
        
        // Khởi tạo mảng với giá trị mặc định
        $genderData = [
            'male' => 0,
            'female' => 0,
            'other' => 0
        ];
        
        // Cập nhật dữ liệu từ kết quả truy vấn
        foreach ($results as $result) {
            if (isset($genderData[$result->gender])) {
                $genderData[$result->gender] = $result->count;
            }
        }
        
        // Tính tổng số lượng
        $total = array_sum($genderData);
        
        // Tính tỉ lệ phần trăm
        $genderRatio = [];
        foreach ($genderData as $gender => $count) {
            $genderRatio[$gender] = $total > 0 ? round(($count / $total) * 100, 2) : 0;
        }
        
        return [
            'data' => [
                ['name' => 'Nam', 'count' => $genderData['male']],
                ['name' => 'Nữ', 'count' => $genderData['female']],
                ['name' => 'Khác', 'count' => $genderData['other']]
            ],
            'total' => $total
        ];
    }
    
    /**
     * Lấy thống kê học viên theo độ tuổi
     */
    public function getStudentsByAgeGroup($range = 'total', $courseItemId = null)
    {
        $query = Student::selectRaw('
            CASE 
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 18 THEN "under_18"
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 18 AND 22 THEN "18_22"
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 23 AND 35 THEN "23_35"
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 36 AND 50 THEN "36_50"
                ELSE "over_50"
            END as age_group, 
            COUNT(*) as count
        ');
        
        // Lọc theo khoảng thời gian nếu cần
        if ($range !== 'total') {
            $today = now();
            
            switch($range) {
                case 'day':
                    $query->whereDate('created_at', $today);
                    break;
                case 'month':
                    $query->whereYear('created_at', $today->year)
                        ->whereMonth('created_at', $today->month);
                    break;
                case 'quarter':
                    $startQuarter = $today->copy()->startOfQuarter();
                    $endQuarter = $today->copy()->endOfQuarter();
                    $query->whereBetween('created_at', [$startQuarter, $endQuarter]);
                    break;
                case 'year':
                    $query->whereYear('created_at', $today->year);
                    break;
            }
        }
        
        // Lọc theo khóa học nếu có
        if ($courseItemId) {
            $query->whereHas('enrollments', function ($q) use ($courseItemId) {
                $q->where('course_item_id', $courseItemId);
            });
        }
        
        $results = $query->groupBy('age_group')->get();
        
        // Khởi tạo mảng với giá trị mặc định
        $ageGroupData = [
            'under_18' => 0,
            '18_22' => 0,
            '23_35' => 0,
            '36_50' => 0,
            'over_50' => 0
        ];
        
        // Cập nhật dữ liệu từ kết quả truy vấn
        foreach ($results as $result) {
            if (isset($ageGroupData[$result->age_group])) {
                $ageGroupData[$result->age_group] = $result->count;
            }
        }
        
        // Tính tổng số lượng
        $total = array_sum($ageGroupData);
        
        // Tính tỉ lệ phần trăm
        $ageGroupRatio = [];
        foreach ($ageGroupData as $ageGroup => $count) {
            $ageGroupRatio[$ageGroup] = $total > 0 ? round(($count / $total) * 100, 2) : 0;
        }
        
        return [
            'labels' => ['<18', '18-22', '23-35', '36-50', '>50'],
            'data' => array_values($ageGroupData),
            'ratio' => array_values($ageGroupRatio),
            'total' => $total
        ];
    }
    
    /**
     * Lấy thống kê học viên theo phương thức học (online/offline)
     */
    public function getStudentsByLearningMode($range = 'total', $courseItemId = null)
    {
        $query = DB::table('enrollments')
            ->join('course_items', 'enrollments.course_item_id', '=', 'course_items.id')
            ->select(DB::raw('COALESCE(course_items.learning_method, "unknown") as learning_mode, COUNT(DISTINCT enrollments.student_id) as count'))
            ->where('enrollments.status', EnrollmentStatus::ACTIVE);

        // Lọc theo khoảng thời gian nếu cần
        if ($range !== 'total') {
            $today = now();
            switch($range) {
                case 'day':
                    $query->whereDate('enrollments.enrollment_date', $today);
                    break;
                case 'month':
                    $query->whereYear('enrollments.enrollment_date', $today->year)
                        ->whereMonth('enrollments.enrollment_date', $today->month);
                    break;
                case 'quarter':
                    $startQuarter = $today->copy()->startOfQuarter();
                    $endQuarter = $today->copy()->endOfQuarter();
                    $query->whereBetween('enrollments.enrollment_date', [$startQuarter, $endQuarter]);
                    break;
                case 'year':
                    $query->whereYear('enrollments.enrollment_date', $today->year);
                    break;
            }
        }

        // Lọc theo khóa học nếu có
        if ($courseItemId) {
            $query->where('enrollments.course_item_id', $courseItemId);
        }

        $results = $query->groupBy('learning_mode')->get();

        // Khởi tạo mảng với giá trị mặc định
        $learningModeData = [
            'online' => 0,
            'offline' => 0,
            'unknown' => 0
        ];

        // Đếm học viên theo phương thức học của khóa học
        foreach ($results as $result) {
            $mode = $result->learning_mode ?? 'unknown';
            if (array_key_exists($mode, $learningModeData)) {
                $learningModeData[$mode] = $result->count;
            }
        }

        // Tính tổng số lượng
        $total = array_sum($learningModeData);

        return [
            'data' => [
                ['name' => 'Trực tuyến', 'count' => $learningModeData['online']],
                ['name' => 'Trực tiếp', 'count' => $learningModeData['offline']],
                ['name' => 'Không xác định', 'count' => $learningModeData['unknown']]
            ],
            'total' => $total
        ];
    }
    
    /**
     * Lấy thống kê học viên theo vùng miền
     */
    public function getStudentsByRegion($range = 'total', $courseItemId = null)
    {
        // Định nghĩa các vùng miền của Việt Nam
        $regions = [
            'north' => 'Miền Bắc',
            'central' => 'Miền Trung',
            'south' => 'Miền Nam',
            'unknown' => 'Không xác định'
        ];
        
        // Khởi tạo mảng với giá trị mặc định
        $regionData = array_fill_keys(array_keys($regions), 0);
        
        // Lấy dữ liệu theo vùng miền thực tế từ province
        $query = Student::query();
        
        // Điều kiện thời gian
        if ($range !== 'total') {
            $today = now();
            
            switch($range) {
                case 'day':
                    $query->whereDate('students.created_at', $today);
                    break;
                case 'month':
                    $query->whereYear('students.created_at', $today->year)
                        ->whereMonth('students.created_at', $today->month);
                    break;
                case 'quarter':
                    $startQuarter = $today->copy()->startOfQuarter();
                    $endQuarter = $today->copy()->endOfQuarter();
                    $query->whereBetween('students.created_at', [$startQuarter, $endQuarter]);
                    break;
                case 'year':
                    $query->whereYear('students.created_at', $today->year);
                    break;
            }
        }
        
        // Lọc theo khóa học nếu có
        if ($courseItemId) {
            $query->whereHas('enrollments', function ($q) use ($courseItemId) {
                $q->where('course_item_id', $courseItemId);
            });
        }
        
        // Lấy tổng số học viên trong điều kiện
        $totalStudents = $query->count();
        
        // Lấy số lượng học viên theo từng miền
        foreach (array_keys($regionData) as $region) {
            if ($region === 'unknown') continue;
            
            $regionCount = (clone $query)->whereHas('province', function ($q) use ($region) {
                $q->where('region', $region);
            })->count();
            
            $regionData[$region] = $regionCount;
        }
        
        // Tính số lượng học viên chưa có tỉnh thành hoặc không xác định
        $regionData['unknown'] = $totalStudents - array_sum($regionData);
        if ($regionData['unknown'] < 0) $regionData['unknown'] = 0;
        
        // Tính tỉ lệ phần trăm
        $regionRatio = [];
        foreach ($regionData as $region => $count) {
            $regionRatio[$region] = $totalStudents > 0 ? round(($count / $totalStudents) * 100, 2) : 0;
        }
        
        return [
            'data' => collect($regions)->map(function($name, $key) use ($regionData) {
                return [
                    'name' => $name,
                    'count' => $regionData[$key]
                ];
            })->values()->toArray(),
            'total' => $totalStudents
        ];
    }

    /**
     * Lấy thông tin danh sách chờ theo khóa học
     */
    public function getWaitingListSummary() 
    {
        // Lấy tổng số học viên trong danh sách chờ
        $totalWaiting = Enrollment::where('status', EnrollmentStatus::WAITING)->count();
        
        // Lấy số lượng học viên chờ theo khóa học
        $waitingByCourse = CourseItem::withCount(['waitingList as count'])
            ->having('count', '>', 0)
            ->orderBy('count', 'desc')
            ->take(10)
            ->get()
            ->map(function($course) use ($totalWaiting) {
                $ratio = $totalWaiting > 0 ? round(($course->count / $totalWaiting) * 100, 2) : 0;
                return [
                    'id' => $course->id,
                    'name' => $course->name,
                    'count' => $course->count,
                    'ratio' => $ratio
                ];
            });
            
        return [
            'total' => $totalWaiting,
            'data' => $waitingByCourse->toArray()
        ];
    }
    
    /**
     * Lấy các thanh toán gần đây
     */
    public function getRecentPayments($limit = 10)
    {
        $payments = Payment::with(['enrollment.student', 'enrollment.courseItem'])
            ->where('status', 'confirmed')
            ->orderBy('payment_date', 'desc')
            ->limit($limit)
            ->get();

        return [
            'data' => $payments->map(function($payment) {
                return [
                    'id' => $payment->id,
                    'student_name' => $payment->enrollment->student->full_name ?? 'N/A',
                    'course_name' => $payment->enrollment->courseItem->name ?? 'N/A',
                    'amount' => $payment->amount,
                    'payment_date' => $payment->payment_date->format('d/m/Y'),
                    'payment_method' => $payment->payment_method
                ];
            }),
            'total' => $payments->count()
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
                $query->where('status', EnrollmentStatus::ACTIVE);
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
    
    /**
     * Lấy thông tin thanh toán chưa hoàn thành
     */
    public function getPendingPayments()
    {
        // Lấy các ghi danh chưa thanh toán đủ
        $unpaidEnrollments = Enrollment::with(['student', 'courseItem'])
            ->where('status', EnrollmentStatus::ACTIVE)
            ->get()
            ->filter(function($enrollment) {
                return $enrollment->getRemainingAmount() > 0;
            });

        $totalPending = $unpaidEnrollments->sum(function($enrollment) {
            return $enrollment->getRemainingAmount();
        });

        return [
            'count' => $unpaidEnrollments->count(),
            'total_pending' => $totalPending,
            'enrollments' => $unpaidEnrollments->take(10)->map(function($enrollment) {
                return [
                    'id' => $enrollment->id,
                    'student_name' => $enrollment->student->full_name,
                    'course_name' => $enrollment->courseItem->name,
                    'remaining_amount' => $enrollment->getRemainingAmount(),
                    'final_fee' => $enrollment->final_fee,
                    'paid_amount' => $enrollment->getTotalPaidAmount()
                ];
            })
        ];
    }

    /**
     * Lấy thống kê học viên theo nguồn
     */
    public function getStudentsBySource($timeRange = 'day', $courseItemId = null)
    {
        $query = DB::table('students')
            ->select('source', DB::raw('COUNT(*) as count'))
            ->whereNotNull('source');

        // Lọc theo khoảng thời gian nếu cần
        if ($timeRange !== 'total') {
            $today = now();

            switch($timeRange) {
                case 'day':
                    $query->whereDate('created_at', $today);
                    break;
                case 'month':
                    $query->whereYear('created_at', $today->year)
                        ->whereMonth('created_at', $today->month);
                    break;
                case 'quarter':
                    $startQuarter = $today->copy()->startOfQuarter();
                    $endQuarter = $today->copy()->endOfQuarter();
                    $query->whereBetween('created_at', [$startQuarter, $endQuarter]);
                    break;
                case 'year':
                    $query->whereYear('created_at', $today->year);
                    break;
            }
        }

        // Lọc theo khóa học nếu có
        if ($courseItemId) {
            $query->whereExists(function ($q) use ($courseItemId) {
                $q->select(DB::raw(1))
                  ->from('enrollments')
                  ->whereColumn('enrollments.student_id', 'students.id')
                  ->where('enrollments.course_item_id', $courseItemId);
            });
        }

        $results = $query->groupBy('source')
            ->orderBy('count', 'desc')
            ->get();

        $total = $results->sum('count');

        $data = $results->map(function ($item) use ($total) {
            $sourceEnum = \App\Enums\StudentSource::fromString($item->source);
            return [
                'source' => $item->source,
                'label' => $sourceEnum ? $sourceEnum->label() : $item->source,
                'icon' => $sourceEnum ? $sourceEnum->icon() : 'fas fa-question',
                'color' => $sourceEnum ? $sourceEnum->color() : 'secondary',
                'count' => $item->count,
                'percentage' => $total > 0 ? round(($item->count / $total) * 100, 1) : 0
            ];
        });

        return [
            'data' => $data,
            'total' => $total,
            'time_range' => $timeRange
        ];
    }

    /**
     * Lấy dữ liệu tổng hợp cho dashboard mới
     */
    public function getDashboardData($timeRange = 'day')
    {
        return [
            'summary' => $this->getSummary(),
            'revenue_by_time' => $this->getRevenueByTimeRange($timeRange, 7),
            'revenue_by_courses' => $this->getRevenueByCoursesWithRatio($timeRange),
            'students_by_courses' => $this->getStudentsByCoursesWithRatio($timeRange),
            'students_by_gender' => $this->getStudentsByGender($timeRange),
            'students_by_age' => $this->getStudentsByAgeGroup($timeRange),
            'students_by_learning_mode' => $this->getStudentsByLearningMode($timeRange),
            'students_by_region' => $this->getStudentsByRegion($timeRange),
            'students_by_source' => $this->getStudentsBySource($timeRange),
            'waiting_list' => $this->getWaitingListSummary(),
            'recent_payments' => $this->getRecentPayments(5),
            'pending_payments' => $this->getPendingPayments()
        ];
    }
}
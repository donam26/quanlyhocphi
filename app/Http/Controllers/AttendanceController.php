<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\CourseItem;
use App\Models\Enrollment;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    /**
     * Hiển thị trang chủ điểm danh
     */
    public function index(Request $request)
    {
        $query = Attendance::with(['enrollment.student', 'enrollment.courseItem'])
                    ->orderBy('class_date', 'desc');
        
        // Lọc theo ngày
        if ($request->filled('date_from')) {
            $query->whereDate('class_date', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('class_date', '<=', $request->date_to);
        }
        
        // Lọc theo trạng thái
        if ($request->filled('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }
        
        // Lọc theo khóa học
        if ($request->filled('course_item_id')) {
            $query->whereHas('enrollment', function ($q) use ($request) {
                $q->where('course_item_id', $request->course_item_id);
            });
        }
        
        // Lọc theo học viên
        if ($request->filled('student_id')) {
            $query->whereHas('enrollment', function ($q) use ($request) {
                $q->where('student_id', $request->student_id);
            });
        }
        
        $attendances = $query->paginate(20);
        $courseItems = CourseItem::where('is_leaf', true)->where('active', true)->orderBy('name')->get();
        $students = Student::orderBy('full_name')->get();
        
        return view('attendance.index', compact('attendances', 'courseItems', 'students'));
    }

    /**
     * Hiển thị form điểm danh theo khóa học
     */
    public function createByCourse(Request $request, CourseItem $courseItem)
    {
        // Lấy ngày điểm danh (mặc định là hôm nay)
        $attendanceDate = $request->filled('attendance_date') 
            ? $request->attendance_date 
            : Carbon::today()->format('Y-m-d');
        
        // Xác định ID của khóa học con nếu là khóa học cha
        $childCourseItemIds = [$courseItem->id]; // Bắt đầu với ID của khóa hiện tại
        
        // Nếu không phải nút lá (có thể có các khóa con), lấy tất cả ID khóa con
        if (!$courseItem->is_leaf) {
            $childCourseItems = $this->getAllChildrenLeafItems($courseItem);
            $childCourseItemIds = $childCourseItems->pluck('id')->toArray();
        } else {
            $childCourseItems = collect([$courseItem]);
        }
        
        // Lấy tất cả ghi danh của các khóa học con
        $enrollments = Enrollment::whereIn('course_item_id', $childCourseItemIds)
            ->where('status', 'enrolled') // Chỉ lấy học viên đang học
            ->with('student', 'courseItem')
            ->get();
        
        // Lấy điểm danh hiện có cho ngày được chọn
        $existingAttendances = Attendance::whereIn('enrollment_id', $enrollments->pluck('id'))
            ->whereDate('class_date', $attendanceDate)
            ->get()
            ->keyBy('enrollment_id'); // Sử dụng enrollment_id làm khóa để dễ tìm kiếm
        
        // Tạo lịch tháng
        $currentMonth = Carbon::parse($attendanceDate)->startOfMonth();
        $calendar = $this->generateCalendar($currentMonth, $courseItem->id);
        
        // Lấy thống kê điểm danh trong tháng
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();
        
        // Tạo mảng chứa thống kê điểm danh theo ngày
        $attendanceStats = $this->getMonthlyAttendanceStats($childCourseItemIds, $startOfMonth, $endOfMonth);
        
        return view('attendance.course', compact(
            'courseItem', 
            'attendanceDate', 
            'enrollments', 
            'existingAttendances', 
            'childCourseItems',
            'calendar',
            'currentMonth',
            'attendanceStats'
        ));
    }

    /**
     * Lưu điểm danh cho khóa học
     */
    public function storeByCourse(Request $request, CourseItem $courseItem)
    {
        $request->validate([
            'attendance_date' => 'required|date',
            'attendances' => 'required|array',
            'attendances.*.enrollment_id' => 'required|exists:enrollments,id',
            'attendances.*.status' => 'required|in:present,absent,late,excused',
            'attendances.*.notes' => 'nullable|string|max:255',
        ]);

        $attendanceDate = $request->attendance_date;
        
        DB::beginTransaction();
        try {
            foreach ($request->attendances as $attendanceData) {
                Attendance::updateOrCreate(
                    [
                        'enrollment_id' => $attendanceData['enrollment_id'],
                        'class_date' => $attendanceDate,
                    ],
                    [
                        'status' => $attendanceData['status'],
                        'notes' => $attendanceData['notes'] ?? null,
                    ]
                );
            }
            
            DB::commit();
            return redirect()->route('course-items.attendance.by-date', [
                'courseItem' => $courseItem->id, 
                'date' => $attendanceDate
            ])->with('success', 'Điểm danh đã được lưu thành công!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Có lỗi xảy ra khi lưu điểm danh: ' . $e->getMessage()]);
        }
    }

    /**
     * Hiển thị điểm danh theo ngày
     */
    public function showByDate(CourseItem $courseItem, $date)
    {
        $attendanceDate = $date;
        
        // Xác định ID của khóa học con nếu là khóa học cha
        $childCourseItemIds = [$courseItem->id]; // Bắt đầu với ID của khóa hiện tại
        
        // Nếu không phải nút lá (có thể có các khóa con), lấy tất cả ID khóa con
        if (!$courseItem->is_leaf) {
            $childCourseItems = $this->getAllChildrenLeafItems($courseItem);
            $childCourseItemIds = $childCourseItems->pluck('id')->toArray();
        } else {
            $childCourseItems = collect([$courseItem]);
        }
        
        // Lấy tất cả ghi danh của các khóa học con
        $enrollments = Enrollment::whereIn('course_item_id', $childCourseItemIds)
            ->where('status', 'enrolled') // Chỉ lấy học viên đang học
            ->with('student', 'courseItem')
            ->get();
        
        // Lấy điểm danh hiện có cho ngày được chọn
        $existingAttendances = Attendance::whereIn('enrollment_id', $enrollments->pluck('id'))
            ->whereDate('class_date', $attendanceDate)
            ->get()
            ->keyBy('enrollment_id');
        
        // Tính toán thống kê
        $stats = [
            'total_students' => $enrollments->count(),
            'present' => $existingAttendances->where('status', 'present')->count(),
            'absent' => $existingAttendances->where('status', 'absent')->count(),
            'late' => $existingAttendances->where('status', 'late')->count(),
            'excused' => $existingAttendances->where('status', 'excused')->count(),
        ];
        
        return view('attendance.course-by-date', compact(
            'courseItem', 
            'attendanceDate', 
            'enrollments', 
            'existingAttendances', 
            'childCourseItems',
            'stats'
        ));
    }

    /**
     * Tạo điểm danh nhanh
     */
    public function quickAttendance(Request $request)
    {
        $request->validate([
            'course_item_id' => 'required|exists:course_items,id',
            'class_date' => 'required|date',
            'status' => 'required|in:present,absent,late,excused',
        ]);
        
        $courseItem = CourseItem::findOrFail($request->course_item_id);
        $classDate = $request->class_date;
        $status = $request->status;
        
        // Lấy tất cả ghi danh cho khóa học
        $enrollments = Enrollment::where('course_item_id', $courseItem->id)
            ->where('status', 'enrolled')
            ->get();
        
        DB::beginTransaction();
        try {
            foreach ($enrollments as $enrollment) {
                Attendance::updateOrCreate(
                    [
                        'enrollment_id' => $enrollment->id,
                        'class_date' => $classDate,
                    ],
                    [
                        'status' => $status,
                        'notes' => $request->notes ?? null,
                    ]
                );
            }
            
            DB::commit();
            return redirect()->route('course-items.attendance.by-date', [
                'courseItem' => $courseItem->id, 
                'date' => $classDate
            ])->with('success', 'Điểm danh nhanh đã được áp dụng thành công!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Có lỗi xảy ra khi áp dụng điểm danh nhanh: ' . $e->getMessage()]);
        }
    }

    /**
     * Xuất báo cáo điểm danh
     */
    public function exportReport(Request $request)
    {
        // Triển khai xuất báo cáo điểm danh theo yêu cầu
        // (Có thể sử dụng package maatwebsite/excel để xuất Excel)
    }

    /**
     * Hiển thị báo cáo điểm danh của học viên
     */
    public function studentReport(Student $student)
    {
        // Lấy tất cả ghi danh của học viên
        $enrollments = $student->enrollments()->where('status', 'enrolled')->with('courseItem')->get();
        
        // Lấy tất cả điểm danh của học viên
        $attendances = Attendance::whereIn('enrollment_id', $enrollments->pluck('id'))
            ->orderBy('class_date', 'desc')
            ->get();
        
        // Nhóm điểm danh theo khóa học
        $attendancesByEnrollment = [];
        foreach ($enrollments as $enrollment) {
            $courseAttendances = $attendances->where('enrollment_id', $enrollment->id);
            
            // Tính thống kê
            $stats = [
                'total' => $courseAttendances->count(),
                'present' => $courseAttendances->where('status', 'present')->count(),
                'absent' => $courseAttendances->where('status', 'absent')->count(),
                'late' => $courseAttendances->where('status', 'late')->count(),
                'excused' => $courseAttendances->where('status', 'excused')->count(),
            ];
            
            // Tính tỷ lệ đi học
            $stats['attendance_rate'] = $stats['total'] > 0 
                ? round((($stats['present'] + $stats['late'] + $stats['excused']) / $stats['total']) * 100, 1) 
                : 0;
            
            $attendancesByEnrollment[] = [
                'enrollment' => $enrollment,
                'course_name' => $enrollment->courseItem->name,
                'attendances' => $courseAttendances,
                'stats' => $stats
            ];
        }
        
        // Thống kê tổng thể
        $overallStats = [
            'total' => $attendances->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'excused' => $attendances->where('status', 'excused')->count(),
        ];
        
        // Tính tỷ lệ đi học tổng thể
        $overallStats['attendance_rate'] = $overallStats['total'] > 0 
            ? round((($overallStats['present'] + $overallStats['late'] + $overallStats['excused']) / $overallStats['total']) * 100, 1) 
            : 0;
        
        return view('attendance.student-report', compact('student', 'attendancesByEnrollment', 'overallStats'));
    }

    /**
     * Lấy tất cả các khóa học con là nút lá từ một khóa học
     */
    private function getAllChildrenLeafItems(CourseItem $courseItem): Collection
    {
        $leafItems = collect();
        
        // Nếu là nút lá, trả về chính nó
        if ($courseItem->is_leaf) {
            return collect([$courseItem]);
        }
        
        // Lặp qua tất cả con trực tiếp
        foreach ($courseItem->children as $child) {
            if ($child->is_leaf) {
                $leafItems->push($child);
            } else {
                // Nếu không phải nút lá, gọi đệ quy để lấy các nút lá con
                $leafItems = $leafItems->merge($this->getAllChildrenLeafItems($child));
            }
        }
        
        return $leafItems;
    }
    
    /**
     * Tạo lịch tháng cho trang điểm danh
     */
    private function generateCalendar(Carbon $month, $courseItemId)
    {
        $calendar = [];
        
        // Clone để tránh thay đổi ngày ban đầu
        $start = $month->copy()->startOfMonth()->startOfWeek();
        $end = $month->copy()->endOfMonth()->endOfWeek();
        
        $currentDay = $start->copy();
        while ($currentDay->lte($end)) {
            $week = [];
            
            for ($i = 0; $i < 7; $i++) {
                $date = $currentDay->copy();
                $week[] = [
                    'date' => $date,
                    'is_current_month' => $date->month == $month->month,
                    'is_today' => $date->isToday(),
                    'url' => route('course-items.attendance.by-date', ['courseItem' => $courseItemId, 'date' => $date->format('Y-m-d')])
                ];
                $currentDay->addDay();
            }
            
            $calendar[] = $week;
        }
        
        return $calendar;
    }
    
    /**
     * Lấy thống kê điểm danh theo tháng
     */
    private function getMonthlyAttendanceStats(array $courseItemIds, Carbon $startDate, Carbon $endDate)
    {
        // Lấy tất cả ghi danh
        $enrollments = Enrollment::whereIn('course_item_id', $courseItemIds)
            ->where('status', 'enrolled')
            ->get();
        
        // Lấy tất cả điểm danh trong tháng
        $attendances = Attendance::whereIn('enrollment_id', $enrollments->pluck('id'))
            ->whereDate('class_date', '>=', $startDate)
            ->whereDate('class_date', '<=', $endDate)
            ->get();
        
        // Nhóm theo ngày
        $attendancesByDate = $attendances->groupBy(function ($attendance) {
            return Carbon::parse($attendance->class_date)->toDateString();
        });
        
        // Tạo thống kê
        $stats = [];
        $startCopy = $startDate->copy();
        while ($startCopy <= $endDate) {
            $dateStr = $startCopy->toDateString();
            $dayAttendances = $attendancesByDate->get($dateStr, collect());
            
            $stats[$dateStr] = [
                'total' => $dayAttendances->count(),
                'present' => $dayAttendances->where('status', 'present')->count(),
                'absent' => $dayAttendances->where('status', 'absent')->count(),
                'late' => $dayAttendances->where('status', 'late')->count(),
                'excused' => $dayAttendances->where('status', 'excused')->count(),
            ];
            
            $startCopy->addDay();
        }
        
        return $stats;
    }

    /**
     * Tạo mô hình dữ liệu Attendance nếu chưa có
     */
    private function createAttendanceModel()
    {
        // Kiểm tra xem bảng migrations có tồn tại đủ trường không
        $migration = "2025_07_12_042502_create_attendances_for_new_structure.php";
        
        // Nếu migration này đã tồn tại nhưng chưa đầy đủ, tạo một migration mới
        $newMigration = "2025_07_25_000000_update_attendances_table.php";
        
        // TODO: Tạo migration cập nhật bảng attendances nếu cần
    }
}

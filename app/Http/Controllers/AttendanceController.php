<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\CourseItem;
use App\Models\Enrollment;
use App\Models\Student;
use App\Services\AttendanceService;
use App\Enums\EnrollmentStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Hiển thị trang chủ điểm danh
     */
    public function index(Request $request)
    {
        $filters = [
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'status' => $request->status,
            'course_item_id' => $request->course_item_id,
            'student_id' => $request->student_id
        ];
        
        $attendances = $this->attendanceService->getAttendances($filters);
        $courseItems = CourseItem::where('is_leaf', true)->where('active', true)->orderBy('name')->get();
        $students = Student::orderBy('first_name')->orderBy('last_name')->get();
        
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
        
        // Lấy thông tin điểm danh của khóa học theo ngày
        $attendanceData = $this->attendanceService->getAttendancesByCourseAndDate($courseItem, $attendanceDate);
        
        // Tạo lịch tháng
        $currentMonth = Carbon::parse($attendanceDate)->startOfMonth();
        $calendar = $this->attendanceService->generateCalendar($currentMonth, $courseItem->id);
        
        // Lấy thống kê điểm danh trong tháng
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();
        
        // Tạo mảng chứa thống kê điểm danh theo ngày
        $attendanceStats = $this->attendanceService->getMonthlyAttendanceStats($courseItem, $startOfMonth, $endOfMonth);
        
        return view('attendance.course', [
            'courseItem' => $courseItem, 
            'attendanceDate' => $attendanceDate, 
            'enrollments' => $attendanceData['enrollments'], 
            'existingAttendances' => $attendanceData['existingAttendances'], 
            'childCourseItems' => $attendanceData['childCourseItems'],
            'calendar' => $calendar,
            'currentMonth' => $currentMonth,
            'attendanceStats' => $attendanceStats
        ]);
    }

    /**
     * Lưu điểm danh cho khóa học
     */
    public function storeByCourse(Request $request, CourseItem $courseItem)
    {
        $validated = $request->validate([
            'attendance_date' => 'required|date',
            'attendances' => 'required|array',
            'attendances.*.enrollment_id' => 'required|exists:enrollments,id',
            'attendances.*.status' => 'required|in:present,absent,late,excused',
            'attendances.*.notes' => 'nullable|string|max:255',
        ]);

        try {
            $this->attendanceService->saveAttendances($validated, $validated['attendance_date']);
            
            return redirect()->route('course-items.attendance.by-date', [
                'courseItem' => $courseItem->id, 
                'date' => $validated['attendance_date']
            ])->with('success', 'Điểm danh đã được lưu thành công!');
            
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Có lỗi xảy ra khi lưu điểm danh: ' . $e->getMessage()]);
        }
    }

    /**
     * Hiển thị điểm danh theo ngày
     */
    public function showByDate(CourseItem $courseItem, $date)
    {
        $attendanceDate = $date;
        $attendanceData = $this->attendanceService->getAttendanceStatsByDate($courseItem, $attendanceDate);
        
        return view('attendance.course-by-date', [
            'courseItem' => $courseItem, 
            'attendanceDate' => $attendanceDate, 
            'enrollments' => $attendanceData['enrollments'], 
            'existingAttendances' => $attendanceData['existingAttendances'], 
            'childCourseItems' => $attendanceData['childCourseItems'],
            'stats' => $attendanceData['stats']
        ]);
    }

    /**
     * Tạo điểm danh nhanh
     */
    public function quickAttendance(Request $request)
    {
        $validated = $request->validate([
            'course_item_id' => 'required|exists:course_items,id',
            'attendance_date' => 'required|date',
            'status' => 'required|in:present,absent,late,excused',
        ]);
        
        $courseItem = CourseItem::findOrFail($validated['course_item_id']);
        $attendanceDate = $validated['attendance_date'];
        $status = $validated['status'];
        
        try {
            $this->attendanceService->applyQuickAttendance($courseItem, $attendanceDate, $status, $request->notes);
            
            return redirect()->route('course-items.attendance.by-date', [
                'courseItem' => $courseItem->id, 
                'date' => $attendanceDate
            ])->with('success', 'Điểm danh nhanh đã được áp dụng thành công!');
            
        } catch (\Exception $e) {
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
        $report = $this->attendanceService->getStudentAttendanceReport($student);
        
        return view('attendance.student-report', [
            'student' => $student, 
            'attendancesByEnrollment' => $report['attendancesByEnrollment'], 
            'overallStats' => $report['overallStats']
        ]);
    }

    /**
     * Hiển thị trang điểm danh dạng cây (tree view)
     */
    public function tree(Request $request)
    {
        // Lấy các ngành học gốc (root items)
        $rootItems = CourseItem::whereNull('parent_id')
            ->where('active', true)
            ->with(['children' => function($query) {
                $query->where('active', true)->orderBy('order_index');
            }])
            ->orderBy('order_index')
            ->get();

        // Load đệ quy tất cả children
        $rootItems->load('children.children.children');

        $currentRootItem = null;
        if ($request->has('root_id')) {
            $currentRootItem = $rootItems->where('id', $request->root_id)->first();
        }

        return view('attendance.tree', compact('rootItems', 'currentRootItem'));
    }

    /**
     * Lấy danh sách học viên để điểm danh cho khóa học
     * Nếu là khóa cha, sẽ lấy tất cả học viên của khóa con
     */
    public function getStudentsForAttendance(CourseItem $courseItem, Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));

        // Kiểm tra xem khóa học có đang active không
        $isActive = $courseItem->isActive();

        // Load children để có thể lấy tất cả khóa con
        $courseItem->load('children.children.children');

        // Lấy tất cả ID của khóa học này và các khóa con
        $courseItemIds = [$courseItem->id];
        $this->getAllChildrenIds($courseItem, $courseItemIds);

        // Lấy điểm danh đã có (nếu có) cho tất cả khóa học
        $existingAttendances = Attendance::whereIn('course_item_id', $courseItemIds)
            ->where('attendance_date', $date)
            ->get()
            ->keyBy('enrollment_id');

        // Lấy danh sách học viên được sắp xếp theo khóa học
        $students = $this->getStudentsGroupedByCourse($courseItemIds, $existingAttendances);

        return response()->json([
            'success' => true,
            'course' => [
                'id' => $courseItem->id,
                'name' => $courseItem->name,
                'path' => $courseItem->path,
                'is_leaf' => $courseItem->is_leaf,
                'is_active' => $isActive,
                'status' => $courseItem->getStatusEnum()->value,
                'status_label' => $courseItem->getStatusEnum()->label(),
                'status_badge' => $courseItem->status_badge
            ],
            'date' => $date,
            'formatted_date' => \Carbon\Carbon::parse($date)->format('d/m/Y'),
            'day_name' => \Carbon\Carbon::parse($date)->locale('vi')->dayName,
            'students' => $students,
            'attendance_exists' => $existingAttendances->count() > 0,
            'total_students' => $students->count(),
            'can_take_attendance' => $isActive // Chỉ cho phép điểm danh nếu khóa học đang active
        ]);
    }

    /**
     * Lưu điểm danh từ tree view
     */
    public function saveAttendanceFromTree(Request $request)
    {
        $validated = $request->validate([
            'course_item_id' => 'required|exists:course_items,id',
            'date' => 'required|date',
            'attendances' => 'required|array',
            'attendances.*.enrollment_id' => 'required|exists:enrollments,id',
            'attendances.*.status' => 'required|in:present,absent,late,excused',
            'attendances.*.notes' => 'nullable|string|max:255'
        ]);

        try {
            DB::beginTransaction();

            $courseItem = CourseItem::findOrFail($validated['course_item_id']);
            
            // Kiểm tra xem khóa học có đang active không
            if (!$courseItem->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể điểm danh cho khóa học đã kết thúc. Chỉ có thể xem danh sách học viên.'
                ], 403);
            }
            
            $date = $validated['date'];

            // Xóa điểm danh cũ (nếu có) - xóa theo enrollment_id và date để tránh duplicate
            $enrollmentIds = collect($validated['attendances'])->pluck('enrollment_id');
            Attendance::whereIn('enrollment_id', $enrollmentIds)
                ->where('attendance_date', $date)
                ->delete();

            // Tạo điểm danh mới
            foreach ($validated['attendances'] as $attendanceData) {
                $enrollment = Enrollment::findOrFail($attendanceData['enrollment_id']);
                
                Attendance::create([
                    'enrollment_id' => $enrollment->id,
                    'student_id' => $enrollment->student_id,
                    'course_item_id' => $enrollment->course_item_id,
                    'attendance_date' => $date,
                    'status' => $attendanceData['status'],
                    'notes' => $attendanceData['notes'] ?? null
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Điểm danh đã được lưu thành công!',
                'total_students' => count($validated['attendances']),
                'present_count' => collect($validated['attendances'])->where('status', 'present')->count(),
                'absent_count' => collect($validated['attendances'])->where('status', 'absent')->count(),
                'late_count' => collect($validated['attendances'])->where('status', 'late')->count(),
                'excused_count' => collect($validated['attendances'])->where('status', 'excused')->count()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Attendance save error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lưu điểm danh: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export điểm danh với tùy chọn columns và filters
     */
    public function exportAttendance(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:course_items,id',
            'columns' => 'array',
            'columns.*' => 'string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|string|in:present,absent,late',
            'enrollmentStatus' => 'nullable|string|in:waiting,active,completed,cancelled',
            'paymentStatus' => 'nullable|string|in:unpaid,partial,paid,no_fee'
        ]);

        $courseItem = CourseItem::findOrFail($validated['course_id']);

        try {
            // Default columns nếu không có columns được chọn
            $columns = $validated['columns'] ?? [
                'student_name', 'student_phone', 'attendance_date',
                'status', 'check_in_time'
            ];

            $fileName = 'diem_danh_' . Str::slug($courseItem->name) . '_' . now()->format('Y_m_d') . '.xlsx';

            return Excel::download(
                new \App\Exports\AttendanceExport(
                    $courseItem,
                    $columns,
                    $validated['start_date'] ?? null,
                    $validated['end_date'] ?? null,
                    $validated
                ),
                $fileName
            );

        } catch (\Exception $e) {
            Log::error('Export attendance error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi export: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method để lấy tất cả ID của khóa học con
     */
    private function getAllChildrenIds($courseItem, &$courseItemIds)
    {
        if ($courseItem->children && $courseItem->children->count() > 0) {
            foreach ($courseItem->children as $child) {
                $courseItemIds[] = $child->id;
                $this->getAllChildrenIds($child, $courseItemIds);
            }
        }
    }

    /**
     * Lấy danh sách học viên được nhóm và sắp xếp theo khóa học
     */
    private function getStudentsGroupedByCourse($courseItemIds, $existingAttendances)
    {
        // Lấy tất cả khóa học theo thứ tự order_index
        $courseItems = CourseItem::whereIn('id', $courseItemIds)
            ->orderBy('order_index')
            ->orderBy('id')
            ->get()
            ->keyBy('id');

        // Lấy enrollments nhóm theo course_item_id
        $enrollmentsByCourse = Enrollment::whereIn('course_item_id', $courseItemIds)
            ->where('status', EnrollmentStatus::ACTIVE->value)
            ->with(['student', 'courseItem'])
            ->get()
            ->groupBy('course_item_id');

        $students = collect();

        // Duyệt qua từng khóa học theo thứ tự đã sắp xếp
        foreach ($courseItemIds as $courseItemId) {
            if (!isset($enrollmentsByCourse[$courseItemId])) {
                continue;
            }

            $courseEnrollments = $enrollmentsByCourse[$courseItemId];

            // Sắp xếp học viên trong khóa học theo tên
            $courseEnrollments = $courseEnrollments->sortBy(function($enrollment) {
                return $enrollment->student->full_name;
            });

            // Thêm học viên của khóa học này vào danh sách
            foreach ($courseEnrollments as $enrollment) {
                $attendance = $existingAttendances->get($enrollment->id);

                $students->push([
                    'enrollment_id' => $enrollment->id,
                    'student_id' => $enrollment->student->id,
                    'student_name' => $enrollment->student->full_name,
                    'student_phone' => $enrollment->student->phone,
                    'student_email' => $enrollment->student->email,
                    'course_name' => $enrollment->courseItem->name,
                    'course_id' => $enrollment->courseItem->id,
                    'current_status' => $attendance ? $attendance->status : 'present',
                    'current_notes' => $attendance ? $attendance->notes : '',
                    'attendance_id' => $attendance ? $attendance->id : null
                ]);
            }
        }

        return $students;
    }
}

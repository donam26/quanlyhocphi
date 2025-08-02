<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\CourseItem;
use App\Models\Enrollment;
use App\Models\Student;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
}

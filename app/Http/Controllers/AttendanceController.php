<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\CourseClass;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Hiển thị danh sách điểm danh
     */
    public function index(Request $request)
    {
        $query = Attendance::with(['enrollment.student', 'enrollment.courseClass.course']);

        // Lọc theo lớp học
        if ($request->filled('course_class_id')) {
            $query->whereHas('enrollment', function($q) use ($request) {
                $q->where('course_class_id', $request->course_class_id);
            });
        }

        // Lọc theo ngày
        if ($request->filled('class_date')) {
            $query->where('class_date', $request->class_date);
        }

        // Lọc theo trạng thái
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $attendances = $query->orderBy('class_date', 'desc')->paginate(20);
        $courseClasses = CourseClass::with('course')->get();

        return view('attendance.index', compact('attendances', 'courseClasses'));
    }

    /**
     * Hiển thị form điểm danh cho lớp học
     */
    public function create(Request $request)
    {
        $courseClass = null;
        $classDate = $request->get('class_date', today()->toDateString());

        if ($request->filled('course_class_id')) {
            $courseClass = CourseClass::with(['course', 'enrollments.student'])
                                     ->findOrFail($request->course_class_id);
        }

        $courseClasses = CourseClass::with('course')
                                   ->whereIn('status', ['in_progress', 'open'])
                                   ->get();

        return view('attendance.create', compact('courseClass', 'courseClasses', 'classDate'));
    }

    /**
     * Lưu điểm danh hàng loạt
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'course_class_id' => 'required|exists:course_classes,id',
            'class_date' => 'required|date',
            'attendances' => 'required|array',
            'attendances.*.enrollment_id' => 'required|exists:enrollments,id',
            'attendances.*.status' => 'required|in:present,absent,late,excused',
            'attendances.*.start_time' => 'nullable|date_format:H:i',
            'attendances.*.end_time' => 'nullable|date_format:H:i',
            'attendances.*.notes' => 'nullable|string',
        ]);

        $courseClass = CourseClass::findOrFail($validatedData['course_class_id']);
        $classDate = $validatedData['class_date'];

        // Kiểm tra đã điểm danh ngày này chưa
        $existingAttendance = Attendance::whereHas('enrollment', function($q) use ($courseClass) {
                                       $q->where('course_class_id', $courseClass->id);
                                   })
                                   ->where('class_date', $classDate)
                                   ->exists();

        if ($existingAttendance) {
            return back()->withErrors(['error' => 'Đã có điểm danh cho ngày này rồi!']);
        }

        DB::beginTransaction();
        try {
            foreach ($validatedData['attendances'] as $attendanceData) {
                // Kiểm tra enrollment thuộc lớp học này
                $enrollment = Enrollment::where('id', $attendanceData['enrollment_id'])
                                       ->where('course_class_id', $courseClass->id)
                                       ->first();

                if (!$enrollment) {
                    continue;
                }

                Attendance::create([
                    'enrollment_id' => $attendanceData['enrollment_id'],
                    'class_date' => $classDate,
                    'start_time' => $attendanceData['start_time'] ?? null,
                    'end_time' => $attendanceData['end_time'] ?? null,
                    'status' => $attendanceData['status'],
                    'notes' => $attendanceData['notes'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('attendance.show-class', [
                'course_class_id' => $courseClass->id,
                'class_date' => $classDate
            ])->with('success', 'Điểm danh thành công!');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    /**
     * Hiển thị điểm danh của một lớp trong ngày
     */
    public function showClass(Request $request)
    {
        $courseClassId = $request->get('course_class_id');
        $classDate = $request->get('class_date', today()->toDateString());

        $courseClass = CourseClass::with(['course', 'enrollments.student'])
                                 ->findOrFail($courseClassId);

        $attendances = Attendance::with(['enrollment.student'])
                                ->whereHas('enrollment', function($q) use ($courseClassId) {
                                    $q->where('course_class_id', $courseClassId);
                                })
                                ->where('class_date', $classDate)
                                ->get()
                                ->keyBy('enrollment_id');

        $stats = [
            'total_students' => $courseClass->enrollments->where('status', 'enrolled')->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'excused' => $attendances->where('status', 'excused')->count(),
        ];

        return view('attendance.show-class', compact('courseClass', 'attendances', 'classDate', 'stats'));
    }

    /**
     * Hiển thị chi tiết điểm danh
     */
    public function show(Attendance $attendance)
    {
        $attendance->load(['enrollment.student', 'enrollment.courseClass.course']);

        return view('attendance.show', compact('attendance'));
    }

    /**
     * Hiển thị form chỉnh sửa điểm danh
     */
    public function edit(Attendance $attendance)
    {
        $attendance->load(['enrollment.student', 'enrollment.courseClass.course']);

        return view('attendance.edit', compact('attendance'));
    }

    /**
     * Cập nhật điểm danh
     */
    public function update(Request $request, Attendance $attendance)
    {
        $validatedData = $request->validate([
            'class_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'status' => 'required|in:present,absent,late,excused',
            'notes' => 'nullable|string',
        ]);

        $attendance->update($validatedData);

        return redirect()->route('attendance.show', $attendance)
                        ->with('success', 'Cập nhật điểm danh thành công!');
    }

    /**
     * Xóa điểm danh
     */
    public function destroy(Attendance $attendance)
    {
        try {
            $attendance->delete();
            return redirect()->route('attendance.index')
                            ->with('success', 'Xóa điểm danh thành công!');
        } catch (\Exception $e) {
            return redirect()->route('attendance.index')
                            ->with('error', 'Không thể xóa điểm danh!');
        }
    }

    /**
     * Báo cáo điểm danh của học viên
     */
    public function studentReport(Student $student)
    {
        $attendances = Attendance::with(['enrollment.courseClass.course'])
                                ->whereHas('enrollment', function($q) use ($student) {
                                    $q->where('student_id', $student->id);
                                })
                                ->orderBy('class_date', 'desc')
                                ->get();

        $stats = [
            'total_classes' => $attendances->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'excused' => $attendances->where('status', 'excused')->count(),
        ];

        $stats['attendance_rate'] = $stats['total_classes'] > 0 
                                  ? (($stats['present'] + $stats['late']) / $stats['total_classes']) * 100 
                                  : 0;

        return view('attendance.student-report', compact('student', 'attendances', 'stats'));
    }

    /**
     * Báo cáo điểm danh của lớp học
     */
    public function classReport(CourseClass $courseClass, Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());

        $attendances = Attendance::with(['enrollment.student'])
                                ->whereHas('enrollment', function($q) use ($courseClass) {
                                    $q->where('course_class_id', $courseClass->id);
                                })
                                ->whereBetween('class_date', [$dateFrom, $dateTo])
                                ->get();

        // Thống kê theo học viên
        $studentStats = $attendances->groupBy('enrollment.student.id')
                                   ->map(function($studentAttendances, $studentId) {
                                       $student = $studentAttendances->first()->enrollment->student;
                                       $total = $studentAttendances->count();
                                       $present = $studentAttendances->where('status', 'present')->count();
                                       $late = $studentAttendances->where('status', 'late')->count();
                                       $absent = $studentAttendances->where('status', 'absent')->count();
                                       $excused = $studentAttendances->where('status', 'excused')->count();

                                       return [
                                           'student' => $student,
                                           'total' => $total,
                                           'present' => $present,
                                           'late' => $late,
                                           'absent' => $absent,
                                           'excused' => $excused,
                                           'attendance_rate' => $total > 0 ? (($present + $late) / $total) * 100 : 0
                                       ];
                                   })
                                   ->sortByDesc('attendance_rate');

        // Thống kê theo ngày
        $dailyStats = $attendances->groupBy('class_date')
                                 ->map(function($dayAttendances, $date) {
                                     $total = $dayAttendances->count();
                                     $present = $dayAttendances->where('status', 'present')->count();
                                     $late = $dayAttendances->where('status', 'late')->count();
                                     $absent = $dayAttendances->where('status', 'absent')->count();

                                     return [
                                         'date' => $date,
                                         'total' => $total,
                                         'present' => $present,
                                         'late' => $late,
                                         'absent' => $absent,
                                         'attendance_rate' => $total > 0 ? (($present + $late) / $total) * 100 : 0
                                     ];
                                 })
                                 ->sortBy('date');

        return view('attendance.class-report', compact('courseClass', 'studentStats', 'dailyStats', 'dateFrom', 'dateTo'));
    }

    /**
     * API điểm danh nhanh
     */
    public function quickAttendance(Request $request)
    {
        $validatedData = $request->validate([
            'enrollment_id' => 'required|exists:enrollments,id',
            'class_date' => 'required|date',
            'status' => 'required|in:present,absent,late,excused',
        ]);

        // Kiểm tra đã điểm danh chưa
        $existingAttendance = Attendance::where('enrollment_id', $validatedData['enrollment_id'])
                                       ->where('class_date', $validatedData['class_date'])
                                       ->first();

        if ($existingAttendance) {
            $existingAttendance->update(['status' => $validatedData['status']]);
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật điểm danh thành công!'
            ]);
        }

        Attendance::create($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Điểm danh thành công!'
        ]);
    }

    /**
     * Xuất báo cáo điểm danh
     */
    public function exportReport(Request $request)
    {
        // Logic xuất Excel sẽ được implement sau
        return response()->json(['message' => 'Chức năng xuất báo cáo đang được phát triển']);
    }
}

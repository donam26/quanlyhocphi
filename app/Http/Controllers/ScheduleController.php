<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\CourseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Enrollment;
use App\Models\Attendance;

class ScheduleController extends Controller
{
    /**
     * Hiển thị danh sách lịch học với calendar view
     */
    public function index(Request $request)
    {
        // Lấy tham số filter
        $courseItemId = $request->get('course_item_id');
        $viewType = $request->get('view', 'calendar'); // calendar hoặc list
        
        // Query cơ bản - chỉ lấy lịch gốc (không kế thừa)
        $query = Schedule::with(['courseItem'])
                        ->original()
                        ->active()
                        ->orderBy('start_date', 'desc');

        // Filter theo khóa học
        if ($courseItemId) {
            $query->where('course_item_id', $courseItemId);
        }

        // Lấy danh sách lịch cho list view
        $schedules = $query->paginate(15);
        
        // Lấy danh sách khóa học cha (không phải lá) để filter
        $parentCourses = CourseItem::where('is_leaf', false)
                                  ->where('active', true)
                                  ->orderBy('name')
                                  ->get();

        return view('schedules.index', compact('schedules', 'parentCourses', 'courseItemId', 'viewType'));
    }

    /**
     * API endpoint để lấy events cho calendar
     */
    public function getCalendarEvents(Request $request)
    {
        $start = $request->get('start');
        $end = $request->get('end');
        $courseItemId = $request->get('course_item_id');

        // Query lịch học - chỉ lấy khóa cha (is_leaf = false)
        $query = Schedule::with(['courseItem'])
                        ->active()
                        ->whereHas('courseItem', function($q) {
                            $q->where('is_leaf', false);
                        });

        // Filter theo khóa học nếu có
        if ($courseItemId) {
            $query->where('course_item_id', $courseItemId);
        }

        // Filter theo thời gian
        if ($start && $end) {
            $query->inDateRange($start, $end);
        }

        $schedules = $query->get();
        
        // Tạo events cho calendar
        $events = collect();
        
        foreach ($schedules as $schedule) {
            $scheduleEvents = $schedule->generateCalendarEvents($start, $end);
            $events = $events->merge($scheduleEvents);
        }

        return response()->json($events->values());
    }

    /**
     * Hiển thị form tạo lịch học mới
     */
    public function create()
    {
        // Chỉ lấy khóa học cha (không phải lá) để tạo lịch
        $parentCourses = CourseItem::where('is_leaf', false)
                                  ->where('active', true)
                                  ->orderBy('name')
                                  ->get();

        return view('schedules.create', compact('parentCourses'));
    }

    /**
     * Lưu lịch học mới
     */
    public function store(Request $request)
    {
        $request->validate([
            'course_item_id' => 'required|exists:course_items,id',
            'days_of_week' => 'required|array|min:1',
            'days_of_week.*' => 'integer|between:1,7',
            'start_date' => 'required|date|after_or_equal:today',
            'end_type' => 'required|in:manual,fixed',
            'end_date' => 'required_if:end_type,fixed|nullable|date|after:start_date'
        ]);

        try {
            DB::beginTransaction();

            // Kiểm tra khóa học có phải là khóa cha không
            $courseItem = CourseItem::findOrFail($request->course_item_id);
            if ($courseItem->is_leaf) {
                return back()->withErrors(['course_item_id' => 'Chỉ có thể tạo lịch cho khóa học cha!']);
            }

            // Kiểm tra trùng lặp lịch
            $existingSchedule = Schedule::where('course_item_id', $request->course_item_id)
                                      ->where('is_inherited', false)
                                      ->where('active', true)
                                      ->first();
            
            if ($existingSchedule) {
                return back()->withErrors(['course_item_id' => 'Khóa học này đã có lịch học! Vui lòng chỉnh sửa lịch hiện tại.']);
            }

            $scheduleData = [
                'course_item_id' => $request->course_item_id,
                'days_of_week' => $request->days_of_week,
                'start_date' => $request->start_date,
                'end_type' => $request->end_type,
                'active' => true,
                'is_inherited' => false
            ];

            // Chỉ set end_date nếu là kiểu cố định
            if ($request->end_type === 'fixed') {
                $scheduleData['end_date'] = $request->end_date;
            }

            $schedule = Schedule::create($scheduleData);

            DB::commit();

            return redirect()->route('schedules.index')
                           ->with('success', 'Đã tạo lịch học thành công! Lịch sẽ được áp dụng cho tất cả khóa con.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Schedule creation error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    /**
     * Hiển thị chi tiết lịch học
     */
    public function show(Schedule $schedule)
    {
        $schedule->load(['courseItem', 'childSchedules.courseItem']);
        
        // Tạo sample events để hiển thị
        $sampleEvents = $schedule->generateCalendarEvents(
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        );
        
        return view('schedules.show', compact('schedule', 'sampleEvents'));
    }

    /**
     * Hiển thị form chỉnh sửa lịch học
     */
    public function edit(Schedule $schedule)
    {
        // Chỉ cho phép chỉnh sửa lịch gốc
        if ($schedule->is_inherited) {
            return redirect()->route('schedules.show', $schedule->parent_schedule_id)
                           ->withErrors(['error' => 'Không thể chỉnh sửa lịch kế thừa. Vui lòng chỉnh sửa lịch gốc.']);
        }

        $parentCourses = CourseItem::where('is_leaf', false)
                                  ->where('active', true)
                                  ->orderBy('name')
                                  ->get();

        return view('schedules.edit', compact('schedule', 'parentCourses'));
    }

    /**
     * Cập nhật lịch học
     */
    public function update(Request $request, Schedule $schedule)
    {
        // Chỉ cho phép cập nhật lịch gốc
        if ($schedule->is_inherited) {
            return back()->withErrors(['error' => 'Không thể chỉnh sửa lịch kế thừa!']);
        }

        $request->validate([
            'days_of_week' => 'required|array|min:1',
            'days_of_week.*' => 'integer|between:1,7',
            'start_date' => 'required|date',
            'end_type' => 'required|in:manual,fixed',
            'end_date' => 'required_if:end_type,fixed|nullable|date|after:start_date'
        ]);

        try {
            DB::beginTransaction();

            $updateData = [
                'days_of_week' => $request->days_of_week,
                'start_date' => $request->start_date,
                'end_type' => $request->end_type
            ];

            // Xử lý end_date dựa trên end_type
            if ($request->end_type === 'fixed') {
                $updateData['end_date'] = $request->end_date;
            } else {
                // Nếu chuyển từ fixed sang manual, set end_date xa trong tương lai
                $updateData['end_date'] = Carbon::parse($request->start_date)->addYears(10);
            }

            $schedule->update($updateData);

            DB::commit();

            return redirect()->route('schedules.show', $schedule)
                           ->with('success', 'Đã cập nhật lịch học thành công! Thay đổi sẽ được áp dụng cho tất cả khóa con.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Schedule update error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    /**
     * Xóa lịch học
     */
    public function destroy(Schedule $schedule)
    {
        // Chỉ cho phép xóa lịch gốc
        if ($schedule->is_inherited) {
            return back()->withErrors(['error' => 'Không thể xóa lịch kế thừa!']);
        }

        try {
            DB::beginTransaction();

            // Xóa tất cả lịch con trước
            $schedule->childSchedules()->delete();
            
            // Xóa lịch gốc
            $schedule->delete();

            DB::commit();

            return redirect()->route('schedules.index')
                           ->with('success', 'Đã xóa lịch học và tất cả lịch con thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Schedule deletion error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    /**
     * Toggle trạng thái hoạt động
     */
    public function toggleActive(Schedule $schedule)
    {
        if ($schedule->is_inherited) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể thay đổi trạng thái lịch kế thừa!'
            ], 422);
        }

        try {
            $schedule->update(['active' => !$schedule->active]);
            
            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật trạng thái thành công!',
                'active' => $schedule->active
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy lịch học theo khóa học (AJAX)
     */
    public function getSchedulesByCourse($courseItemId)
    {
        $schedules = Schedule::where('course_item_id', $courseItemId)
                           ->active()
                           ->with('courseItem')
                           ->get()
                           ->map(function($schedule) {
                               return [
                                   'id' => $schedule->id,
                                   'course_name' => $schedule->courseItem->name,
                                   'days_of_week_names' => $schedule->days_of_week_names,
                                   'start_date' => $schedule->start_date->format('d/m/Y'),
                                   'end_date' => $schedule->end_date ? $schedule->end_date->format('d/m/Y') : null,
                                   'is_inherited' => $schedule->is_inherited
                               ];
                           });

        return response()->json([
            'success' => true,
            'schedules' => $schedules
        ]);
    }

    /**
     * Lấy thông tin buổi học để điểm danh
     */
    public function getSessionInfo(Request $request)
    {
        $validated = $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'date' => 'required|date'
        ]);

        $schedule = Schedule::with(['courseItem'])->findOrFail($validated['schedule_id']);
        $date = $validated['date'];

        // Lấy danh sách học viên đã ghi danh vào khóa học
        $enrollments = Enrollment::where('course_item_id', $schedule->course_item_id)
            ->where('status', 'enrolled')
            ->with(['student'])
            ->get();

        // Lấy điểm danh đã có (nếu có)
        $existingAttendances = Attendance::where('schedule_id', $schedule->id)
            ->where('attendance_date', $date)
            ->get()
            ->keyBy('enrollment_id');

        // Chuẩn bị dữ liệu cho response
        $students = $enrollments->map(function($enrollment) use ($existingAttendances) {
            $attendance = $existingAttendances->get($enrollment->id);
            
            return [
                'enrollment_id' => $enrollment->id,
                'student_id' => $enrollment->student->id,
                'student_name' => $enrollment->student->full_name,
                'student_phone' => $enrollment->student->phone,
                'current_status' => $attendance ? $attendance->status : null,
                'current_notes' => $attendance ? $attendance->notes : null,
                'attendance_id' => $attendance ? $attendance->id : null
            ];
        });

        return response()->json([
            'success' => true,
            'session' => [
                'schedule_id' => $schedule->id,
                'course_name' => $schedule->courseItem->name,
                'course_path' => $schedule->courseItem->path,
                'date' => $date,
                'formatted_date' => \Carbon\Carbon::parse($date)->format('d/m/Y'),
                'day_name' => \Carbon\Carbon::parse($date)->locale('vi')->dayName,
                'is_inherited' => $schedule->is_inherited
            ],
            'students' => $students,
            'attendance_exists' => $existingAttendances->count() > 0
        ]);
    }

    /**
     * Lưu điểm danh từ calendar
     */
    public function saveAttendance(Request $request)
    {
        $validated = $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'date' => 'required|date',
            'attendances' => 'required|array',
            'attendances.*.enrollment_id' => 'required|exists:enrollments,id',
            'attendances.*.status' => 'required|in:present,absent,late,excused',
            'attendances.*.notes' => 'nullable|string|max:255'
        ]);

        try {
            DB::beginTransaction();

            $schedule = Schedule::findOrFail($validated['schedule_id']);
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
                    'schedule_id' => $schedule->id,
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
                'absent_count' => collect($validated['attendances'])->where('status', 'absent')->count()
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
     * Lấy thống kê điểm danh cho calendar events
     */
    public function getAttendanceStats(Request $request)
    {
        $start = $request->get('start');
        $end = $request->get('end');
        $courseItemId = $request->get('course_item_id');

        // Query lịch học
        $query = Schedule::with(['courseItem'])
                        ->active()
                        ->whereHas('courseItem', function($q) {
                            $q->where('is_leaf', false);
                        });

        if ($courseItemId) {
            $query->where('course_item_id', $courseItemId);
        }

        if ($start && $end) {
            $query->inDateRange($start, $end);
        }

        $schedules = $query->get();
        $attendanceStats = [];

        foreach ($schedules as $schedule) {
            $events = $schedule->generateCalendarEvents($start, $end);
            
            foreach ($events as $event) {
                $eventDate = $event['date'];
                
                // Đếm số học viên đã điểm danh
                $attendanceCount = Attendance::where('schedule_id', $schedule->id)
                    ->where('attendance_date', $eventDate)
                    ->count();
                
                // Đếm tổng số học viên trong khóa
                $totalStudents = Enrollment::where('course_item_id', $schedule->course_item_id)
                    ->where('status', 'active')
                    ->count();

                $attendanceStats[$event['id']] = [
                    'attended' => $attendanceCount,
                    'total' => $totalStudents,
                    'has_attendance' => $attendanceCount > 0,
                    'completion_rate' => $totalStudents > 0 ? round(($attendanceCount / $totalStudents) * 100) : 0
                ];
            }
        }

        return response()->json([
            'success' => true,
            'stats' => $attendanceStats
        ]);
    }

    /**
     * Đóng khóa học thủ công
     */
    public function closeSchedule(Schedule $schedule)
    {
        try {
            if (!$schedule->canCloseManually()) {
                return back()->withErrors(['error' => 'Không thể đóng khóa học này!']);
            }

            $schedule->closeManually();

            return back()->with('success', 'Đã đóng khóa học thành công!');

        } catch (\Exception $e) {
            Log::error('Schedule close error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }
}

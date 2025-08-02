<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\CourseItem;
use App\Services\ScheduleService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    protected $scheduleService;
    
    public function __construct(ScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    /**
     * Hiển thị trang lịch học
     */
    public function index(Request $request)
    {
        // Lấy tháng hiện tại hoặc tháng được chọn
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);
        
        // Tạo đối tượng Carbon cho tháng được chọn
        $currentMonth = Carbon::createFromDate($year, $month, 1);
        
        // Lấy danh sách các khóa học đang hoạt động
        $courseItems = CourseItem::where('is_leaf', true)
                                ->where('active', true)
                                ->orderBy('name')
                                ->get();
                                
        // Lấy tất cả lịch học trong tháng
        $schedules = $this->scheduleService->getMonthSchedules($month, $year);
        
        // Tạo danh sách các ngày có lịch học
        $scheduleDates = $this->scheduleService->getScheduleDatesByMonth($schedules, $month, $year);
        
        // Tạo lịch tháng
        $calendar = $this->scheduleService->generateCalendar($currentMonth);
        
        return view('schedules.index', compact('calendar', 'currentMonth', 'scheduleDates', 'courseItems', 'schedules'));
    }
    
    /**
     * Hiển thị lịch học của một khóa học cụ thể
     */
    public function showCourseSchedule(Request $request, CourseItem $courseItem)
    {
        // Lấy tháng hiện tại hoặc tháng được chọn
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);
        
        // Tạo đối tượng Carbon cho tháng được chọn
        $currentMonth = Carbon::createFromDate($year, $month, 1);
        
        // Lấy tất cả lịch học trong tháng cho khóa học này
        $schedules = $this->scheduleService->getMonthSchedules($month, $year, $courseItem->id);
        
        // Tạo danh sách các ngày có lịch học
        $scheduleDates = $this->scheduleService->getScheduleDatesByMonth($schedules, $month, $year);
        
        // Tạo lịch tháng
        $calendar = $this->scheduleService->generateCalendar($currentMonth);
        
        // Lấy số lượng học viên đăng ký khóa học này
        $enrollmentCount = $this->scheduleService->getEnrollmentCountForCourse($courseItem->id);
        
        return view('schedules.course', compact('calendar', 'currentMonth', 'scheduleDates', 'courseItem', 'enrollmentCount', 'schedules'));
    }
    
    /**
     * Hiển thị form tạo lịch học mới
     */
    public function create(Request $request)
    {
        $courseItem = null;
        if ($request->filled('course_item_id')) {
            $courseItem = CourseItem::findOrFail($request->course_item_id);
        }
        
        $courseItems = CourseItem::where('is_leaf', true)
                                ->where('active', true)
                                ->orderBy('name')
                                ->get();
        
        $date = $request->filled('date') ? $request->date : now()->format('Y-m-d');
        
        return view('schedules.create', compact('courseItems', 'courseItem', 'date'));
    }
    
    /**
     * Lưu lịch học mới
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_item_id' => 'required|exists:course_items,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
            'is_recurring' => 'boolean',
            'recurring_days' => 'required_if:is_recurring,1|array',
            'recurring_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
        ]);
        
        $schedule = $this->scheduleService->createSchedule($validated);
        
        return redirect()->route('course-items.schedules', $validated['course_item_id'])
                         ->with('success', 'Đã tạo lịch học thành công!');
    }
    
    /**
     * Hiển thị form chỉnh sửa lịch học
     */
    public function edit(Schedule $schedule)
    {
        $courseItems = CourseItem::where('is_leaf', true)
                                ->where('active', true)
                                ->orderBy('name')
                                ->get();
        
        return view('schedules.edit', compact('schedule', 'courseItems'));
    }
    
    /**
     * Cập nhật lịch học
     */
    public function update(Request $request, Schedule $schedule)
    {
        $validated = $request->validate([
            'course_item_id' => 'required|exists:course_items,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
            'is_recurring' => 'boolean',
            'recurring_days' => 'required_if:is_recurring,1|array',
            'recurring_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
        ]);
        
        $this->scheduleService->updateSchedule($schedule, $validated);
        
        return redirect()->route('course-items.schedules', $validated['course_item_id'])
                         ->with('success', 'Đã cập nhật lịch học thành công!');
    }
    
    /**
     * Xóa lịch học
     */
    public function destroy(Schedule $schedule)
    {
        $courseItemId = $schedule->course_item_id;
        $this->scheduleService->deleteSchedule($schedule);
        
        return redirect()->route('course-items.schedules', $courseItemId)
                         ->with('success', 'Đã xóa lịch học thành công!');
    }
}

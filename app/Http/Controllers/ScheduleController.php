<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\CourseItem;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ScheduleController extends Controller
{
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
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();
        
        $schedules = Schedule::betweenDates($startOfMonth, $endOfMonth)
                            ->where('active', true)
                            ->with('courseItem')
                            ->orderBy('start_date')
                            ->get();
        
        // Tạo danh sách các ngày có lịch học
        $scheduleDates = [];
        foreach ($schedules as $schedule) {
            if (!$schedule->is_recurring) {
                // Lịch học đơn lẻ
                if ($schedule->start_date->between($startOfMonth, $endOfMonth)) {
                    $dateStr = $schedule->start_date->format('Y-m-d');
                    if (!isset($scheduleDates[$dateStr])) {
                        $scheduleDates[$dateStr] = [];
                    }
                    $scheduleDates[$dateStr][] = $schedule;
                }
            } else {
                // Lịch học định kỳ
                $currentDate = max($startOfMonth, $schedule->start_date->copy());
                $endDate = $schedule->end_date ? min($endOfMonth, $schedule->end_date) : $endOfMonth;
                
                while ($currentDate->lte($endDate)) {
                    $dayOfWeek = strtolower($currentDate->format('l'));
                    if (in_array($dayOfWeek, $schedule->recurring_days ?: [])) {
                        $dateStr = $currentDate->format('Y-m-d');
                        if (!isset($scheduleDates[$dateStr])) {
                            $scheduleDates[$dateStr] = [];
                        }
                        $scheduleDates[$dateStr][] = $schedule;
                    }
                    $currentDate->addDay();
                }
            }
        }
        
        // Tạo lịch tháng
        $calendar = $this->generateCalendar($currentMonth);
        
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
        
        // Lấy tất cả lịch học của khóa học
        $schedules = Schedule::where('course_item_id', $courseItem->id)
                            ->where('active', true)
                            ->orderBy('start_date')
                            ->get();
        
        // Lấy tất cả lịch học trong tháng
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();
        
        // Tạo danh sách các ngày có lịch học
        $scheduleDates = [];
        foreach ($schedules as $schedule) {
            if (!$schedule->is_recurring) {
                // Lịch học đơn lẻ
                if ($schedule->start_date->between($startOfMonth, $endOfMonth)) {
                    $dateStr = $schedule->start_date->format('Y-m-d');
                    if (!isset($scheduleDates[$dateStr])) {
                        $scheduleDates[$dateStr] = [];
                    }
                    $scheduleDates[$dateStr][] = $schedule;
                }
            } else {
                // Lịch học định kỳ
                $currentDate = max($startOfMonth, $schedule->start_date->copy());
                $endDate = $schedule->end_date ? min($endOfMonth, $schedule->end_date) : $endOfMonth;
                
                while ($currentDate->lte($endDate)) {
                    $dayOfWeek = strtolower($currentDate->format('l'));
                    if (in_array($dayOfWeek, $schedule->recurring_days ?: [])) {
                        $dateStr = $currentDate->format('Y-m-d');
                        if (!isset($scheduleDates[$dateStr])) {
                            $scheduleDates[$dateStr] = [];
                        }
                        $scheduleDates[$dateStr][] = $schedule;
                    }
                    $currentDate->addDay();
                }
            }
        }
        
        // Tạo lịch tháng
        $calendar = $this->generateCalendar($currentMonth);
        
        // Lấy số lượng học viên đăng ký khóa học này
        $enrollmentCount = Enrollment::where('course_item_id', $courseItem->id)
                                    ->where('status', 'enrolled')
                                    ->count();
        
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
        $request->validate([
            'course_item_id' => 'required|exists:course_items,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
            'is_recurring' => 'boolean',
            'recurring_days' => 'required_if:is_recurring,1|array',
            'recurring_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
        ]);
        
        // Xác định thứ trong tuần từ ngày được chọn
        $dayOfWeek = Carbon::parse($request->start_date)->format('l');
        $dayOfWeekMap = [
            'Monday' => 'T2',
            'Tuesday' => 'T3',
            'Wednesday' => 'T4',
            'Thursday' => 'T5',
            'Friday' => 'T6',
            'Saturday' => 'T7',
            'Sunday' => 'CN',
        ];
        
        // Tạo lịch học mới
        $schedule = Schedule::create([
            'course_item_id' => $request->course_item_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'day_of_week' => $dayOfWeekMap[$dayOfWeek] ?? $dayOfWeek,
            'recurring_days' => $request->is_recurring ? $request->recurring_days : null,
            'notes' => $request->notes,
            'is_recurring' => $request->has('is_recurring'),
            'active' => true,
        ]);
        
        return redirect()->route('course-items.schedules', $request->course_item_id)
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
        $request->validate([
            'course_item_id' => 'required|exists:course_items,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
            'is_recurring' => 'boolean',
            'recurring_days' => 'required_if:is_recurring,1|array',
            'recurring_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
        ]);
        
        // Xác định thứ trong tuần từ ngày được chọn
        $dayOfWeek = Carbon::parse($request->start_date)->format('l');
        $dayOfWeekMap = [
            'Monday' => 'T2',
            'Tuesday' => 'T3',
            'Wednesday' => 'T4',
            'Thursday' => 'T5',
            'Friday' => 'T6',
            'Saturday' => 'T7',
            'Sunday' => 'CN',
        ];
        
        // Cập nhật lịch học
        $schedule->update([
            'course_item_id' => $request->course_item_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'day_of_week' => $dayOfWeekMap[$dayOfWeek] ?? $dayOfWeek,
            'recurring_days' => $request->is_recurring ? $request->recurring_days : null,
            'notes' => $request->notes,
            'is_recurring' => $request->has('is_recurring'),
        ]);
        
        return redirect()->route('course-items.schedules', $request->course_item_id)
                         ->with('success', 'Đã cập nhật lịch học thành công!');
    }
    
    /**
     * Xóa lịch học
     */
    public function destroy(Schedule $schedule)
    {
        $courseItemId = $schedule->course_item_id;
        $schedule->delete();
        
        return redirect()->route('course-items.schedules', $courseItemId)
                         ->with('success', 'Đã xóa lịch học thành công!');
    }
    
    /**
     * Tạo lịch tháng
     */
    private function generateCalendar(Carbon $month)
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
                    'formatted_date' => $date->format('Y-m-d')
                ];
                $currentDay->addDay();
            }
            
            $calendar[] = $week;
        }
        
        return $calendar;
    }
}

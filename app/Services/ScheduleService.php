<?php

namespace App\Services;

use App\Models\CourseItem;
use App\Models\Schedule;
use App\Models\Attendance;
use App\Models\Enrollment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ScheduleService
{
    public function getSchedules($filters = [])
    {
        $query = Schedule::with('courseItem');
        
        if (isset($filters['course_item_id'])) {
            $query->where('course_item_id', $filters['course_item_id']);
        }
        
        if (isset($filters['date_from'])) {
            $query->where('start_date', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('start_date', '<=', $filters['date_to']);
        }
        
        return $query->orderBy('start_date', 'desc')
            ->paginate(isset($filters['per_page']) ? $filters['per_page'] : 15);
    }

    public function getSchedule($id)
    {
        return Schedule::with('courseItem')->findOrFail($id);
    }

    public function createSchedule(array $data)
    {
        DB::beginTransaction();
        
        try {
            // Xác định thứ trong tuần từ ngày được chọn
            $dayOfWeek = Carbon::parse($data['start_date'])->format('l');
            $dayOfWeekMap = [
                'Monday' => 'T2',
                'Tuesday' => 'T3',
                'Wednesday' => 'T4',
                'Thursday' => 'T5',
                'Friday' => 'T6',
                'Saturday' => 'T7',
                'Sunday' => 'CN',
            ];
            
            $isRecurring = isset($data['is_recurring']) && $data['is_recurring'];
            
            // Tạo lịch học mới
            $schedule = Schedule::create([
                'course_item_id' => $data['course_item_id'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'day_of_week' => $dayOfWeekMap[$dayOfWeek] ?? $dayOfWeek,
                'recurring_days' => $isRecurring && isset($data['recurring_days']) ? $data['recurring_days'] : null,
                'notes' => $data['notes'] ?? null,
                'is_recurring' => $isRecurring,
                'active' => true,
            ]);
            
            DB::commit();
            return $schedule;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateSchedule(Schedule $schedule, array $data)
    {
        DB::beginTransaction();
        
        try {
            // Xác định thứ trong tuần từ ngày được chọn
            $dayOfWeek = Carbon::parse($data['start_date'])->format('l');
            $dayOfWeekMap = [
                'Monday' => 'T2',
                'Tuesday' => 'T3',
                'Wednesday' => 'T4',
                'Thursday' => 'T5',
                'Friday' => 'T6',
                'Saturday' => 'T7',
                'Sunday' => 'CN',
            ];
            
            $isRecurring = isset($data['is_recurring']) && $data['is_recurring'];
            
            $schedule->update([
                'course_item_id' => $data['course_item_id'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'day_of_week' => $dayOfWeekMap[$dayOfWeek] ?? $dayOfWeek,
                'recurring_days' => $isRecurring && isset($data['recurring_days']) ? $data['recurring_days'] : null,
                'notes' => $data['notes'] ?? null,
                'is_recurring' => $isRecurring,
            ]);
            
            DB::commit();
            return $schedule;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteSchedule(Schedule $schedule)
    {
        DB::beginTransaction();
        
        try {
            // Xóa các điểm danh liên quan
            Attendance::where('schedule_id', $schedule->id)->delete();
            
            // Xóa lịch học
            $schedule->delete();
            
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getSchedulesByCourse(CourseItem $courseItem)
    {
        return Schedule::where('course_item_id', $courseItem->id)
            ->orderBy('start_date')
            ->get();
    }

    public function getUpcomingSchedules($limit = 5)
    {
        return Schedule::where('start_date', '>=', now())
            ->with('courseItem')
            ->orderBy('start_date')
            ->limit($limit)
            ->get();
    }

    public function getSchedulesByDateRange($startDate, $endDate)
    {
        return Schedule::whereBetween('start_date', [$startDate, $endDate])
            ->orWhereBetween('end_date', [$startDate, $endDate])
            ->orWhere(function($query) use ($startDate, $endDate) {
                $query->where('start_date', '<=', $startDate)
                      ->where('end_date', '>=', $endDate);
            })
            ->with('courseItem')
            ->orderBy('start_date')
            ->get();
    }

    public function getSchedulesForDay($date)
    {
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        
        return Schedule::where(function($query) use ($date, $dayOfWeek) {
                // Lịch học một lần vào ngày cụ thể
                $query->where('start_date', $date)
                      ->whereNull('recurring_days');
            })
            ->orWhere(function($query) use ($date, $dayOfWeek) {
                // Lịch học lặp lại theo ngày trong tuần
                $query->where('start_date', '<=', $date)
                      ->where(function($q) use ($date) {
                          $q->where('end_date', '>=', $date)
                            ->orWhereNull('end_date');
                      })
                      ->whereRaw("FIND_IN_SET(?, recurring_days)", [$dayOfWeek]);
            })
            ->with('courseItem')
            ->orderBy('start_date')
            ->get();
    }

    public function getMonthSchedules($month, $year, $courseItemId = null)
    {
        // Tạo đối tượng Carbon cho tháng được chọn
        $currentMonth = Carbon::createFromDate($year, $month, 1);
        
        // Lấy tất cả lịch học trong tháng
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();
        
        $query = Schedule::betweenDates($startOfMonth, $endOfMonth)
                        ->where('active', true)
                        ->with('courseItem');
                        
        if ($courseItemId) {
            $query->where('course_item_id', $courseItemId);
        }
        
        $schedules = $query->orderBy('start_date')->get();
        
        return $schedules;
    }
    
    public function getScheduleDatesByMonth($schedules, $month, $year)
    {
        // Tạo đối tượng Carbon cho tháng được chọn
        $currentMonth = Carbon::createFromDate($year, $month, 1);
        
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
        
        return $scheduleDates;
    }

    public function generateCalendar(Carbon $month)
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

    public function getEnrollmentCountForCourse($courseItemId)
    {
        return Enrollment::where('course_item_id', $courseItemId)
                        ->where('status', \App\Enums\EnrollmentStatus::ACTIVE->value)
                        ->count();
    }

    public function generateSchedulesFromRecurring(Schedule $schedule)
    {
        if (!$schedule->recurring_days || !$schedule->end_date) {
            return [];
        }
        
        $start = Carbon::parse($schedule->start_date);
        $end = Carbon::parse($schedule->end_date);
        $recurringDays = explode(',', $schedule->recurring_days);
        $dates = [];
        
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if (in_array($date->dayOfWeek, $recurringDays)) {
                $dates[] = $date->format('Y-m-d');
            }
        }
        
        return $dates;
    }
} 
<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\CourseItem;
use App\Models\Enrollment;
use App\Models\Schedule;
use App\Models\Student;
use App\Enums\EnrollmentStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    public function getAttendances($filters = [])
    {
        $query = Attendance::with(['enrollment.student', 'enrollment.courseItem'])
                ->orderBy('attendance_date', 'desc');
        
        // Lọc theo ngày
        if (isset($filters['date_from'])) {
            $query->whereDate('attendance_date', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->whereDate('attendance_date', '<=', $filters['date_to']);
        }
        
        // Lọc theo trạng thái
        if (isset($filters['status']) && $filters['status'] != 'all') {
            $query->where('status', $filters['status']);
        }
        
        // Lọc theo khóa học
        if (isset($filters['course_item_id'])) {
            $query->whereHas('enrollment', function ($q) use ($filters) {
                $q->where('course_item_id', $filters['course_item_id']);
            });
        }
        
        // Lọc theo học viên
        if (isset($filters['student_id'])) {
            $query->whereHas('enrollment', function ($q) use ($filters) {
                $q->where('student_id', $filters['student_id']);
            });
        }
        
        return $query->paginate(isset($filters['per_page']) ? $filters['per_page'] : 20);
    }

    public function getAttendancesByCourseAndDate(CourseItem $courseItem, $date)
    {
        // Lấy tất cả ID của khóa con nếu là khóa học cha
        $childCourseItemIds = $this->getChildCourseItemIds($courseItem);
        
        // Lấy tất cả ghi danh của các khóa học
        $enrollments = Enrollment::whereIn('course_item_id', $childCourseItemIds)
            ->where('status', EnrollmentStatus::ACTIVE->value) // Chỉ lấy học viên đang học
            ->with('student', 'courseItem')
            ->get();
        
        // Lấy điểm danh hiện có cho ngày được chọn
        $existingAttendances = Attendance::whereIn('enrollment_id', $enrollments->pluck('id'))
            ->whereDate('attendance_date', $date)
            ->get()
            ->keyBy('enrollment_id');
            
        return [
            'enrollments' => $enrollments,
            'existingAttendances' => $existingAttendances,
            'childCourseItems' => $this->getAllChildrenLeafItems($courseItem)
        ];
    }

    public function saveAttendances(array $data, $attendanceDate)
    {
        DB::beginTransaction();
        try {
            foreach ($data['attendances'] as $attendanceData) {
                Attendance::updateOrCreate(
                    [
                        'enrollment_id' => $attendanceData['enrollment_id'],
                        'attendance_date' => $attendanceDate,
                    ],
                    [
                        'status' => $attendanceData['status'],
                        'notes' => $attendanceData['notes'] ?? null,
                    ]
                );
            }
            
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getAttendanceStatsByDate(CourseItem $courseItem, $date)
    {
        // Lấy tất cả ID của khóa con nếu là khóa học cha
        $childCourseItemIds = $this->getChildCourseItemIds($courseItem);
        
        // Lấy tất cả ghi danh của các khóa học
        $enrollments = Enrollment::whereIn('course_item_id', $childCourseItemIds)
            ->where('status', EnrollmentStatus::ACTIVE->value) // Chỉ lấy học viên đang học
            ->with('student', 'courseItem')
            ->get();
        
        // Lấy điểm danh hiện có cho ngày được chọn
        $existingAttendances = Attendance::whereIn('enrollment_id', $enrollments->pluck('id'))
            ->whereDate('attendance_date', $date)
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
        
        return [
            'enrollments' => $enrollments,
            'existingAttendances' => $existingAttendances,
            'childCourseItems' => $this->getAllChildrenLeafItems($courseItem),
            'stats' => $stats
        ];
    }

    public function applyQuickAttendance(CourseItem $courseItem, $attendanceDate, $status, $notes = null)
    {
        // Lấy tất cả ghi danh cho khóa học
        $enrollments = Enrollment::where('course_item_id', $courseItem->id)
            ->where('status', EnrollmentStatus::ACTIVE->value)
            ->get();
        
        DB::beginTransaction();
        try {
            foreach ($enrollments as $enrollment) {
                Attendance::updateOrCreate(
                    [
                        'enrollment_id' => $enrollment->id,
                        'attendance_date' => $attendanceDate,
                    ],
                    [
                        'status' => $status,
                        'notes' => $notes,
                    ]
                );
            }
            
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getStudentAttendanceReport(Student $student)
    {
        // Lấy tất cả ghi danh của học viên
        $enrollments = $student->enrollments()->where('status', 'enrolled')->with('courseItem')->get();
        
        // Lấy tất cả điểm danh của học viên
        $attendances = Attendance::whereIn('enrollment_id', $enrollments->pluck('id'))
            ->orderBy('attendance_date', 'desc')
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
            
        return [
            'attendancesByEnrollment' => $attendancesByEnrollment,
            'overallStats' => $overallStats
        ];
    }
    
    public function generateCalendar(Carbon $month, $courseItemId)
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
    
    public function getMonthlyAttendanceStats(CourseItem $courseItem, Carbon $startDate, Carbon $endDate)
    {
        // Lấy tất cả ID của khóa con nếu là khóa học cha
        $courseItemIds = $this->getChildCourseItemIds($courseItem);
        
        // Lấy tất cả ghi danh
        $enrollments = Enrollment::whereIn('course_item_id', $courseItemIds)
            ->where('status', EnrollmentStatus::ACTIVE->value)
            ->get();
        
        // Lấy tất cả điểm danh trong tháng
        $attendances = Attendance::whereIn('enrollment_id', $enrollments->pluck('id'))
            ->whereDate('attendance_date', '>=', $startDate)
            ->whereDate('attendance_date', '<=', $endDate)
            ->get();
        
        // Nhóm theo ngày
        $attendancesByDate = $attendances->groupBy(function ($attendance) {
            return Carbon::parse($attendance->attendance_date)->toDateString();
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
     * Lấy tất cả ID của khóa con (bao gồm cả ID khóa hiện tại)
     */
    public function getChildCourseItemIds(CourseItem $courseItem)
    {
        $ids = [$courseItem->id];
        
        if (!$courseItem->is_leaf) {
            $childItems = $this->getAllChildrenLeafItems($courseItem);
            $childIds = $childItems->pluck('id')->toArray();
            $ids = array_merge($ids, $childIds);
        }
        
        return $ids;
    }

    /**
     * Lấy tất cả các khóa học con là nút lá từ một khóa học
     */
    public function getAllChildrenLeafItems(CourseItem $courseItem): Collection
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
} 
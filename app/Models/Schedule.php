<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_item_id',
        'days_of_week',
        'start_date',
        'end_date',
        'end_type',
        'active',
        'is_inherited',
        'parent_schedule_id'
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'active' => 'boolean',
        'is_inherited' => 'boolean'
    ];

    protected $attributes = [
        'days_of_week' => '[]',
        'active' => true
    ];

    /**
     * Relationship với CourseItem
     */
    public function courseItem()
    {
        return $this->belongsTo(CourseItem::class);
    }

    /**
     * Relationship với lịch cha
     */
    public function parentSchedule()
    {
        return $this->belongsTo(Schedule::class, 'parent_schedule_id');
    }

    /**
     * Relationship với các lịch con
     */
    public function childSchedules()
    {
        return $this->hasMany(Schedule::class, 'parent_schedule_id');
    }

    /**
     * Boot model events
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($schedule) {
            // Chỉ tự động tính end_date nếu không có end_date và end_type là manual
            if ($schedule->start_date && !$schedule->end_date && $schedule->end_type === 'manual') {
                // Đặt end_date rất xa trong tương lai cho khóa học tự đóng
                $schedule->end_date = Carbon::parse($schedule->start_date)->addYears(10);
            }
        });

        static::saved(function ($schedule) {
            // Khi lưu lịch cha, tự động cập nhật cho các khóa con
            if (!$schedule->is_inherited) {
                $schedule->propagateToChildren();
            }
        });
    }

    /**
     * Lan truyền lịch học xuống các khóa con
     */
    public function propagateToChildren()
    {
        $courseItem = $this->courseItem;
        if (!$courseItem) return;

        // Lấy tất cả khóa con
        $childCourses = $this->getAllChildCourses($courseItem);

        foreach ($childCourses as $childCourse) {
            // Xóa lịch cũ của khóa con (nếu là inherited)
            Schedule::where('course_item_id', $childCourse->id)
                ->where('is_inherited', true)
                ->delete();

            // Tạo lịch mới cho khóa con
            Schedule::create([
                'course_item_id' => $childCourse->id,
                'days_of_week' => $this->days_of_week,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'end_type' => $this->end_type,
                'active' => $this->active,
                'is_inherited' => true,
                'parent_schedule_id' => $this->id
            ]);
        }
    }

    /**
     * Lấy tất cả khóa con (đệ quy)
     */
    private function getAllChildCourses($courseItem)
    {
        $children = collect();
        
        foreach ($courseItem->children as $child) {
            $children->push($child);
            $children = $children->merge($this->getAllChildCourses($child));
        }
        
        return $children;
    }

    /**
     * Lấy tên các ngày trong tuần
     */
    public function getDaysOfWeekNamesAttribute()
    {
        $dayNames = [
            1 => 'Thứ 2',
            2 => 'Thứ 3', 
            3 => 'Thứ 4',
            4 => 'Thứ 5',
            5 => 'Thứ 6',
            6 => 'Thứ 7',
            7 => 'Chủ nhật'
        ];

        return collect($this->days_of_week)
            ->map(fn($day) => $dayNames[$day] ?? '')
            ->filter()
            ->implode(', ');
    }

    /**
     * Kiểm tra lịch có đang hoạt động không
     */
    public function isActive()
    {
        if (!$this->active) return false;
        
        $now = Carbon::now();
        
        // Nếu là khóa học tự đóng, chỉ kiểm tra start_date và active status
        if ($this->end_type === 'manual') {
            return $now->gte($this->start_date);
        }
        
        // Nếu là khóa học cố định ngày kết thúc
        return $now->between($this->start_date, $this->end_date);
    }

    /**
     * Đóng khóa học thủ công (cho khóa học tự đóng)
     */
    public function closeManually()
    {
        if ($this->end_type !== 'manual') {
            throw new \Exception('Chỉ có thể đóng thủ công khóa học có kiểu kết thúc "tự đóng"');
        }

        $this->update([
            'end_date' => Carbon::now(),
            'active' => false
        ]);

        // Cập nhật cho các khóa con nếu là lịch gốc
        if (!$this->is_inherited) {
            $this->childSchedules()->update([
                'end_date' => Carbon::now(),
                'active' => false
            ]);
        }
    }

    /**
     * Kiểm tra có thể đóng thủ công không
     */
    public function canCloseManually()
    {
        return $this->end_type === 'manual' && $this->active;
    }



    /**
     * Scope cho lịch đang hoạt động
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope cho lịch không kế thừa (lịch gốc)
     */
    public function scopeOriginal($query)
    {
        return $query->where('is_inherited', false);
    }

    /**
     * Tạo các buổi học cụ thể từ lịch định kỳ cho calendar
     */
    public function generateCalendarEvents($startDate = null, $endDate = null)
    {
        $events = collect();
        
        if (empty($this->days_of_week)) {
            return $events;
        }

        $start = $startDate ? Carbon::parse($startDate) : $this->start_date;
        $end = $endDate ? Carbon::parse($endDate) : $this->end_date;
        
        if (!$start || !$end) {
            return $events;
        }

        // Tạo period từ start đến end
        $period = CarbonPeriod::create($start, $end);
        
        foreach ($period as $date) {
            // Kiểm tra xem ngày này có trong days_of_week không
            $dayOfWeek = $date->dayOfWeek === 0 ? 7 : $date->dayOfWeek; // Convert Sunday từ 0 thành 7
            
            if (in_array($dayOfWeek, $this->days_of_week)) {
                $events->push([
                    'id' => $this->id . '_' . $date->format('Y-m-d'),
                    'schedule_id' => $this->id,
                    'title' => $this->courseItem->name ?? 'Khóa học',
                    'date' => $date->format('Y-m-d'),
                    'start' => $date->format('Y-m-d') . 'T08:00:00', // Default time
                    'end' => $date->format('Y-m-d') . 'T10:00:00',   // Default time
                    'course_name' => $this->courseItem->name ?? '',
                    'course_path' => $this->courseItem->path ?? '',
                    'is_inherited' => $this->is_inherited,
                    'backgroundColor' => $this->getEventColor(),
                    'borderColor' => $this->getEventColor(),
                    'textColor' => '#ffffff'
                ]);
            }
        }
        
        return $events;
    }

    /**
     * Lấy màu sắc cho event dựa trên loại khóa học
     */
    private function getEventColor()
    {
        if ($this->is_inherited) {
            return '#6c757d'; // Gray cho lịch kế thừa
        }

        // Màu sắc dựa trên tên khóa học
        $courseName = strtolower($this->courseItem->name ?? '');
        
        if (str_contains($courseName, 'kế toán')) {
            return '#007bff'; // Blue
        } elseif (str_contains($courseName, 'marketing')) {
            return '#28a745'; // Green
        } elseif (str_contains($courseName, 'quản trị')) {
            return '#ffc107'; // Yellow
        }
        
        return '#17a2b8'; // Teal mặc định
    }

    /**
     * Scope cho lịch trong khoảng thời gian
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->where(function($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function($q2) use ($startDate, $endDate) {
                  $q2->where('start_date', '<=', $startDate)
                     ->where('end_date', '>=', $endDate);
              });
        });
    }
}

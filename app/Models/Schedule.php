<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Schedule extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'course_item_id',
        'start_date',
        'end_date',
        'day_of_week',
        'recurring_days',
        'notes',
        'is_recurring',
        'active'
    ];
    
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'recurring_days' => 'array',
        'is_recurring' => 'boolean',
        'active' => 'boolean'
    ];
    
    /**
     * Lấy khóa học liên quan đến lịch học này
     */
    public function courseItem()
    {
        return $this->belongsTo(CourseItem::class);
    }
    
    /**
     * Lấy danh sách điểm danh cho buổi học này
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
    
    /**
     * Lấy tên thứ trong tuần dạng tiếng Việt
     */
    public function getDayOfWeekNameAttribute()
    {
        $dayNames = [
            'Monday' => 'Thứ 2',
            'Tuesday' => 'Thứ 3',
            'Wednesday' => 'Thứ 4',
            'Thursday' => 'Thứ 5',
            'Friday' => 'Thứ 6',
            'Saturday' => 'Thứ 7',
            'Sunday' => 'Chủ nhật'
        ];
        
        $dayOfWeek = Carbon::parse($this->start_date)->format('l');
        return $dayNames[$dayOfWeek] ?? $dayOfWeek;
    }
    
    /**
     * Lấy chuỗi các ngày học trong tuần
     */
    public function getRecurringDaysTextAttribute()
    {
        if (!$this->recurring_days) {
            return '';
        }
        
        $dayNames = [
            'monday' => 'T2',
            'tuesday' => 'T3',
            'wednesday' => 'T4',
            'thursday' => 'T5',
            'friday' => 'T6',
            'saturday' => 'T7',
            'sunday' => 'CN'
        ];
        
        $days = [];
        foreach ($this->recurring_days as $day) {
            if (isset($dayNames[strtolower($day)])) {
                $days[] = $dayNames[strtolower($day)];
            }
        }
        
        return implode(', ', $days);
    }
    
    /**
     * Scope lọc theo khóa học
     */
    public function scopeForCourse($query, $courseItemId)
    {
        return $query->where('course_item_id', $courseItemId);
    }
    
    /**
     * Scope lọc theo ngày
     */
    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('start_date', '<=', $date)
                    ->where(function($q) use ($date) {
                        $q->whereDate('end_date', '>=', $date)
                          ->orWhereNull('end_date');
                    });
    }
    
    /**
     * Scope lọc theo khoảng thời gian
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->where(function($q) use ($startDate, $endDate) {
            // Lịch học bắt đầu trong khoảng thời gian
            $q->whereBetween('start_date', [$startDate, $endDate])
              // Hoặc lịch học kết thúc trong khoảng thời gian
              ->orWhereBetween('end_date', [$startDate, $endDate])
              // Hoặc lịch học bao trùm khoảng thời gian
              ->orWhere(function($q2) use ($startDate, $endDate) {
                  $q2->where('start_date', '<=', $startDate)
                     ->where(function($q3) use ($endDate) {
                         $q3->where('end_date', '>=', $endDate)
                            ->orWhereNull('end_date');
                     });
              });
        });
    }
    
    /**
     * Scope lọc các lịch học đang hoạt động
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
    
    /**
     * Kiểm tra xem một ngày cụ thể có nằm trong lịch học không
     */
    public function isOnDate($date)
    {
        $date = Carbon::parse($date);
        
        // Kiểm tra ngày có nằm trong khoảng thời gian của lịch học không
        if ($date->lt($this->start_date)) {
            return false;
        }
        
        if ($this->end_date && $date->gt($this->end_date)) {
            return false;
        }
        
        // Nếu không phải lịch học định kỳ, chỉ kiểm tra ngày bắt đầu
        if (!$this->is_recurring) {
            return $date->isSameDay($this->start_date);
        }
        
        // Nếu là lịch học định kỳ, kiểm tra thứ trong tuần
        $dayOfWeek = strtolower($date->format('l'));
        return in_array($dayOfWeek, $this->recurring_days ?: []);
    }
    
    /**
     * Tạo các ngày học cụ thể trong khoảng thời gian
     */
    public function getClassDates()
    {
        $dates = [];
        
        if (!$this->is_recurring) {
            $dates[] = $this->start_date->format('Y-m-d');
            return $dates;
        }
        
        $currentDate = $this->start_date->copy();
        $endDate = $this->end_date ? $this->end_date->copy() : $currentDate->copy()->addMonths(3);
        
        while ($currentDate->lte($endDate)) {
            $dayOfWeek = strtolower($currentDate->format('l'));
            
            if (in_array($dayOfWeek, $this->recurring_days ?: [])) {
                $dates[] = $currentDate->format('Y-m-d');
            }
            
            $currentDate->addDay();
        }
        
        return $dates;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'class_date',
        'status',
        'start_time',
        'end_time',
        'notes'
    ];

    protected $casts = [
        'class_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i'
    ];

    /**
     * Lấy ghi danh liên quan đến điểm danh này
     */
    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Lấy học viên (qua ghi danh)
     */
    public function student()
    {
        return $this->enrollment->student();
    }

    /**
     * Lấy khóa học (qua ghi danh)
     */
    public function courseItem()
    {
        return $this->enrollment->courseItem();
    }

    /**
     * Scope lấy các điểm danh trong khoảng thời gian
     */
    public function scopeBetweenDates($query, $fromDate, $toDate)
    {
        return $query->whereDate('class_date', '>=', $fromDate)
                     ->whereDate('class_date', '<=', $toDate);
    }

    /**
     * Scope lấy các điểm danh của một khóa học
     */
    public function scopeForCourseItem($query, $courseItemId)
    {
        return $query->whereHas('enrollment', function ($q) use ($courseItemId) {
            $q->where('course_item_id', $courseItemId);
        });
    }

    /**
     * Scope lấy các điểm danh của một học viên
     */
    public function scopeForStudent($query, $studentId)
    {
        return $query->whereHas('enrollment', function ($q) use ($studentId) {
            $q->where('student_id', $studentId);
        });
    }

    /**
     * Scope lấy các điểm danh theo trạng thái
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Format class_date theo format Việt Nam
     */
    public function getFormattedDateAttribute()
    {
        return Carbon::parse($this->class_date)->format('d/m/Y');
    }

    /**
     * Trả về tên trạng thái điểm danh bằng tiếng Việt
     */
    public function getStatusNameAttribute()
    {
        $statuses = [
            'present' => 'Có mặt',
            'absent' => 'Vắng mặt',
            'late' => 'Đi muộn',
            'excused' => 'Có phép'
        ];

        return $statuses[$this->status] ?? 'Không xác định';
    }
}

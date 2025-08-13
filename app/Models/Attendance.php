<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Traits\Date;

class Attendance extends Model
{
    use HasFactory, Date;

    protected $fillable = [
        'enrollment_id',
        'course_item_id',
        'student_id',
        'attendance_date',
        'status',
        'start_time',
        'end_time',
        'notes'
    ];

    protected $casts = [
        'attendance_date' => 'date',
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
     * Quan hệ trực tiếp với khóa học
     */
    public function courseItem()
    {
        return $this->belongsTo(CourseItem::class);
    }

    /**
     * Lấy học viên (qua ghi danh)
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Scope lấy các điểm danh trong khoảng thời gian
     */
    public function scopeBetweenDates($query, $fromDate, $toDate)
    {
        return $query->whereDate('attendance_date', '>=', $fromDate)
                     ->whereDate('attendance_date', '<=', $toDate);
    }

    /**
     * Scope lấy các điểm danh của một khóa học
     */
    public function scopeForCourseItem($query, $courseItemId)
    {
        return $query->where('course_item_id', $courseItemId);
    }

    /**
     * Scope lấy các điểm danh của một học viên
     */
    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope lấy các điểm danh theo trạng thái
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Format attendance_date theo format Việt Nam
     */
    public function getFormattedDateAttribute()
    {
        return $this->formatDate('attendance_date');
    }

    /**
     * Chuyển đổi ngày điểm danh từ định dạng dd/mm/yyyy sang Y-m-d khi gán giá trị
     */
    public function setAttendanceDateAttribute($value)
    {
        $this->attributes['attendance_date'] = static::parseDate($value);
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

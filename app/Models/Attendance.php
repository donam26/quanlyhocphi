<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'class_date',
        'start_time',
        'end_time',
        'status',
        'notes'
    ];

    protected $casts = [
        'class_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i'
    ];

    /**
     * Quan hệ với ghi danh
     */
    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Quan hệ với học viên thông qua ghi danh
     */
    public function student()
    {
        return $this->hasOneThrough(Student::class, Enrollment::class, 'id', 'id', 'enrollment_id', 'student_id');
    }

    /**
     * Quan hệ với lớp học thông qua ghi danh
     */
    public function courseClass()
    {
        return $this->hasOneThrough(CourseClass::class, Enrollment::class, 'id', 'id', 'enrollment_id', 'course_class_id');
    }

    /**
     * Scope cho học viên có mặt
     */
    public function scopePresent($query)
    {
        return $query->where('status', 'present');
    }

    /**
     * Scope cho học viên vắng mặt
     */
    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }

    /**
     * Scope cho học viên đi muộn
     */
    public function scopeLate($query)
    {
        return $query->where('status', 'late');
    }

    /**
     * Scope theo ngày
     */
    public function scopeByDate($query, $date)
    {
        return $query->where('class_date', $date);
    }
}

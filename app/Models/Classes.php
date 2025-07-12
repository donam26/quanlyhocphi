<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classes extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_item_id',
        'name',
        'type',
        'batch_number',
        'max_students',
        'start_date',
        'end_date',
        'registration_deadline',
        'status',
        'notes'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'registration_deadline' => 'date',
    ];

    /**
     * Lấy khóa học liên kết
     */
    public function courseItem()
    {
        return $this->belongsTo(CourseItem::class, 'course_item_id');
    }

    /**
     * Lấy các ghi danh của lớp học
     */
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'class_id');
    }

    /**
     * Lấy danh sách học viên đã ghi danh
     */
    public function students()
    {
        return $this->hasManyThrough(Student::class, Enrollment::class, 'class_id', 'id', 'id', 'student_id');
    }

    /**
     * Số lượng học viên đã ghi danh
     */
    public function getStudentCountAttribute()
    {
        return $this->enrollments()->count();
    }

    /**
     * Kiểm tra xem lớp học đã đầy chưa
     */
    public function getIsFullAttribute()
    {
        return $this->student_count >= $this->max_students;
    }

    /**
     * Số chỗ còn trống
     */
    public function getAvailableSeatsAttribute()
    {
        return max(0, $this->max_students - $this->student_count);
    }

    /**
     * Tổng học phí đã thu
     */
    public function getTotalRevenueAttribute()
    {
        return $this->enrollments()
            ->with('payments')
            ->get()
            ->sum(function ($enrollment) {
                return $enrollment->payments->where('status', 'confirmed')->sum('amount');
            });
    }

    /**
     * Lấy tất cả điểm danh của lớp
     */
    public function attendances()
    {
        return $this->hasManyThrough(
            Attendance::class, 
            Enrollment::class,
            'class_id',
            'enrollment_id',
            'id',
            'id'
        );
    }
}

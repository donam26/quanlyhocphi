<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseClass extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'sub_course_id',
        'name',
        'type',
        'batch_number',
        'max_students',
        'start_date',
        'end_date',
        'registration_deadline',
        'status',
        'is_package',
        'parent_class_id',
        'notes'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'registration_deadline' => 'date',
        'is_package' => 'boolean'
    ];

    /**
     * Quan hệ với khóa học
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Quan hệ với khóa nhỏ (nếu có)
     */
    public function subCourse()
    {
        return $this->belongsTo(SubCourse::class);
    }

    /**
     * Quan hệ với lớp cha (nếu là lớp con trong gói)
     */
    public function parentClass()
    {
        return $this->belongsTo(CourseClass::class, 'parent_class_id');
    }

    /**
     * Quan hệ với các lớp con (nếu là gói)
     */
    public function childClasses()
    {
        return $this->hasMany(CourseClass::class, 'parent_class_id');
    }

    /**
     * Quan hệ với các gói khóa học
     */
    public function packages()
    {
        return $this->belongsToMany(CoursePackage::class, 'course_package_classes', 'course_class_id', 'package_id')
                    ->withPivot('order')
                    ->withTimestamps();
    }

    /**
     * Quan hệ với các ghi danh
     */
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Quan hệ với học viên thông qua ghi danh
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'enrollments')
                    ->withPivot('enrollment_date', 'status', 'discount_percentage', 'discount_amount', 'final_fee')
                    ->withTimestamps();
    }

    /**
     * Đếm số học viên hiện tại
     */
    public function getCurrentStudentCount()
    {
        return $this->enrollments()->where('status', 'enrolled')->count();
    }

    /**
     * Kiểm tra lớp có còn chỗ không
     */
    public function hasAvailableSlots()
    {
        return $this->getCurrentStudentCount() < $this->max_students;
    }

    /**
     * Kiểm tra xem lớp có phải là gói không
     */
    public function isPackage()
    {
        return $this->is_package;
    }

    /**
     * Kiểm tra xem lớp có phải là lớp con không
     */
    public function isChildClass()
    {
        return !is_null($this->parent_class_id);
    }
}

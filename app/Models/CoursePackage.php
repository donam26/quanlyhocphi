<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoursePackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'name',
        'description',
        'type',
        'batch_number',
        'package_fee',
        'start_date',
        'end_date',
        'active'
    ];

    protected $casts = [
        'package_fee' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'active' => 'boolean'
    ];

    /**
     * Quan hệ với khóa học chính
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Quan hệ với các lớp học trong gói
     */
    public function classes()
    {
        return $this->belongsToMany(CourseClass::class, 'course_package_classes', 'package_id', 'course_class_id')
                    ->withPivot('order')
                    ->withTimestamps();
    }

    /**
     * Lấy các lớp học theo thứ tự trong gói
     */
    public function orderedClasses()
    {
        return $this->classes()->orderBy('course_package_classes.order');
    }

    /**
     * Thêm lớp học vào gói
     */
    public function addClass(CourseClass $class, $order = null)
    {
        if ($order === null) {
            $order = $this->classes()->count() + 1;
        }
        
        return $this->classes()->attach($class->id, ['order' => $order]);
    }

    /**
     * Xóa lớp học khỏi gói
     */
    public function removeClass(CourseClass $class)
    {
        return $this->classes()->detach($class->id);
    }

    /**
     * Kiểm tra gói là online hay offline
     */
    public function isOnline()
    {
        return $this->type === 'online';
    }

    /**
     * Kiểm tra gói là online hay offline
     */
    public function isOffline()
    {
        return $this->type === 'offline';
    }
} 
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCourse extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'name',
        'description',
        'fee',
        'order',
        'code',
        'has_online',
        'has_offline',
        'active'
    ];

    protected $casts = [
        'fee' => 'decimal:2',
        'has_online' => 'boolean',
        'has_offline' => 'boolean',
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
     * Quan hệ với các lớp học
     */
    public function classes()
    {
        return $this->hasMany(CourseClass::class);
    }

    /**
     * Lấy các lớp online
     */
    public function onlineClasses()
    {
        return $this->hasMany(CourseClass::class)->where('type', 'online');
    }

    /**
     * Lấy các lớp offline
     */
    public function offlineClasses()
    {
        return $this->hasMany(CourseClass::class)->where('type', 'offline');
    }
}

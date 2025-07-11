<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'major_id',
        'name',
        'description',
        'fee',
        'duration',
        'is_complex',
        'course_type',
        'active'
    ];

    protected $casts = [
        'fee' => 'decimal:2',
        'is_complex' => 'boolean',
        'active' => 'boolean'
    ];

    /**
     * Quan hệ với ngành học
     */
    public function major()
    {
        return $this->belongsTo(Major::class);
    }

    /**
     * Quan hệ với các khóa nhỏ
     */
    public function subCourses()
    {
        return $this->hasMany(SubCourse::class);
    }

    /**
     * Quan hệ với các lớp học
     */
    public function classes()
    {
        return $this->hasMany(CourseClass::class);
    }

    /**
     * Quan hệ với các gói khóa học
     */
    public function packages()
    {
        return $this->hasMany(CoursePackage::class);
    }

    /**
     * Quan hệ với danh sách chờ
     */
    public function waitingLists()
    {
        return $this->hasMany(WaitingList::class);
    }

    /**
     * Lấy các lớp học đang hoạt động
     */
    public function activeClasses()
    {
        return $this->hasMany(CourseClass::class)->whereIn('status', ['open', 'in_progress']);
    }

    /**
     * Lấy các gói khóa học online
     */
    public function onlinePackages()
    {
        return $this->hasMany(CoursePackage::class)->where('type', 'online');
    }

    /**
     * Lấy các gói khóa học offline
     */
    public function offlinePackages()
    {
        return $this->hasMany(CoursePackage::class)->where('type', 'offline');
    }

    /**
     * Kiểm tra xem khóa học có phải là khóa phức tạp không
     */
    public function isComplexCourse()
    {
        return $this->course_type === 'complex';
    }
}

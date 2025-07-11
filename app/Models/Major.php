<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Major extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'active'
    ];

    protected $casts = [
        'active' => 'boolean'
    ];

    /**
     * Quan hệ với các khóa học
     */
    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    /**
     * Lấy các khóa học đang hoạt động
     */
    public function activeCourses()
    {
        return $this->hasMany(Course::class)->where('active', true);
    }
}

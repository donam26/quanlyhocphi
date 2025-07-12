<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaitingList extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'course_item_id', // Đổi từ course_id sang course_item_id
        'added_date',
        'interest_level',
        'status',
        'last_contact_date',
        'contact_notes'
    ];

    protected $casts = [
        'added_date' => 'date',
        'last_contact_date' => 'date'
    ];

    /**
     * Quan hệ với học viên
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Quan hệ với khóa học (đổi từ Course sang CourseItem)
     */
    public function courseItem()
    {
        return $this->belongsTo(CourseItem::class, 'course_item_id');
    }

    /**
     * Scope cho danh sách chờ đang chờ
     */
    public function scopeWaiting($query)
    {
        return $query->where('status', 'waiting');
    }

    /**
     * Scope cho danh sách đã liên hệ
     */
    public function scopeContacted($query)
    {
        return $query->where('status', 'contacted');
    }

    /**
     * Scope cho danh sách đã ghi danh
     */
    public function scopeEnrolled($query)
    {
        return $query->where('status', 'enrolled');
    }

    /**
     * Scope cho danh sách không quan tâm
     */
    public function scopeNotInterested($query)
    {
        return $query->where('status', 'not_interested');
    }

    /**
     * Kiểm tra xem học viên có thể ghi danh không
     */
    public function canEnroll()
    {
        return $this->status === 'waiting' || $this->status === 'contacted';
    }

    /**
     * Lấy đường dẫn đầy đủ của khóa học
     */
    public function getCoursePathAttribute()
    {
        return $this->courseItem->path;
    }
}

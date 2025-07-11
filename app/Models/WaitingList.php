<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaitingList extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'course_id',
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
     * Quan hệ với khóa học
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Scope cho danh sách chờ đang hoạt động
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'waiting');
    }

    /**
     * Scope cho danh sách chờ mức độ quan tâm cao
     */
    public function scopeHighInterest($query)
    {
        return $query->where('interest_level', 'high');
    }

    /**
     * Scope cho danh sách chờ cần liên hệ
     */
    public function scopeNeedContact($query)
    {
        return $query->where('status', 'waiting')
                    ->where(function($q) {
                        $q->whereNull('last_contact_date')
                          ->orWhere('last_contact_date', '<', now()->subDays(7));
                    });
    }
}

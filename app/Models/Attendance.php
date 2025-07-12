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
        'start_time' => 'datetime',
        'end_time' => 'datetime'
    ];

    /**
     * Quan hệ với ghi danh
     */
    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Lấy học viên từ quan hệ ghi danh
     */
    public function student()
    {
        return $this->enrollment->student();
    }

    /**
     * Lấy lớp học từ quan hệ ghi danh
     */
    public function class()
    {
        return $this->enrollment->class();
    }

    /**
     * Scope cho các bản ghi có mặt
     */
    public function scopePresent($query)
    {
        return $query->where('status', 'present');
    }

    /**
     * Scope cho các bản ghi vắng mặt
     */
    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }

    /**
     * Scope cho các bản ghi đi trễ
     */
    public function scopeLate($query)
    {
        return $query->where('status', 'late');
    }

    /**
     * Scope cho các bản ghi có phép
     */
    public function scopeExcused($query)
    {
        return $query->where('status', 'excused');
    }

    /**
     * Tính thời gian tham gia (giờ)
     */
    public function getDurationAttribute()
    {
        if ($this->start_time && $this->end_time) {
            return $this->start_time->diffInHours($this->end_time);
        }
        return 0;
    }
}

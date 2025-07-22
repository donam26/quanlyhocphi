<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'course_item_id',
        'enrollment_date',
        'status',
        'discount_percentage',
        'discount_amount',
        'final_fee',
        'notes'
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_fee' => 'decimal:2'
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
    public function courseItem()
    {
        return $this->belongsTo(CourseItem::class, 'course_item_id');
    }

    /**
     * Quan hệ với các thanh toán
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Quan hệ với các điểm danh
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
    
    /**
     * Quan hệ với tiến độ lộ trình học tập
     */
    public function learningPathProgress()
    {
        return $this->hasMany(LearningPathProgress::class);
    }

    /**
     * Tổng số tiền đã thanh toán
     */
    public function getPaidAmountAttribute()
    {
        return $this->payments()->where('status', 'confirmed')->sum('amount');
    }

    /**
     * Số tiền còn thiếu
     */
    public function getRemainingAmountAttribute()
    {
        return max(0, $this->final_fee - $this->paid_amount);
    }

    /**
     * Kiểm tra xem đã thanh toán đủ chưa
     */
    public function getIsFullyPaidAttribute()
    {
        return $this->remaining_amount <= 0;
    }

    /**
     * Kiểm tra xem có khoản thanh toán đang chờ xác nhận không
     */
    public function getHasPendingPaymentAttribute()
    {
        return $this->payments()->where('status', 'pending')->exists();
    }
    
    /**
     * Lấy tiến độ của lộ trình học tập
     */
    public function getPathProgressPercentageAttribute()
    {
        $paths = $this->courseItem->learningPaths;
        
        if ($paths->count() === 0) {
            return 100;
        }
        
        $completedPaths = $this->learningPathProgress()
            ->where('is_completed', true)
            ->count();
            
        return round(($completedPaths / $paths->count()) * 100);
    }
}

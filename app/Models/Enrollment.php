<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    // Các trạng thái đăng ký
    const STATUS_PENDING = 'pending';    // Chờ xử lý
    const STATUS_WAITING = 'waiting';    // Trong danh sách chờ
    const STATUS_CONFIRMED = 'confirmed'; // Đã xác nhận
    const STATUS_ACTIVE = 'active';      // Đang học
    const STATUS_COMPLETED = 'completed'; // Đã hoàn thành
    const STATUS_CANCELLED = 'cancelled'; // Đã hủy

    protected $fillable = [
        'student_id',
        'course_item_id',
        'enrollment_date',
        'status',
        'request_date',
        'confirmation_date',
        'last_status_change',
        'previous_status',
        'discount_percentage',
        'discount_amount',
        'final_fee',
        'notes',
        'custom_fields'
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'request_date' => 'datetime',
        'confirmation_date' => 'datetime',
        'last_status_change' => 'datetime',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_fee' => 'decimal:2',
        'custom_fields' => 'array'
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
     * Quan hệ với bảng tiến độ học tập
     */
    public function learningPathProgress()
    {
        return $this->hasMany(LearningPathProgress::class);
    }
    
    /**
     * Lấy tổng số tiền đã thanh toán
     */
    public function getTotalPaidAmount()
    {
        return $this->payments()->where('status', 'confirmed')->sum('amount');
    }

    /**
     * Số tiền còn thiếu
     */
    public function getRemainingAmount()
    {
        return max(0, $this->final_fee - $this->getTotalPaidAmount());
    }

    /**
     * Kiểm tra xem đã thanh toán đủ chưa
     */
    public function isFullyPaid()
    {
        return $this->getRemainingAmount() <= 0;
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

    /**
     * Kiểm tra xem ghi danh có đang trong danh sách chờ không
     */
    public function isWaiting()
    {
        return $this->status === self::STATUS_WAITING;
    }

    /**
     * Cập nhật trạng thái và lưu trạng thái trước đó
     */
    public function updateStatus($newStatus)
    {
        $this->previous_status = $this->status;
        $this->status = $newStatus;
        $this->last_status_change = now();
        
        if ($newStatus === self::STATUS_CONFIRMED) {
            $this->confirmation_date = now();
        }
        
        $this->save();
        
        return $this;
    }
    
    /**
     * Danh sách các enrollment đang trong danh sách chờ
     */
    public function scopeWaitingList($query)
    {
        return $query->where('status', self::STATUS_WAITING);
    }
    
    /**
     * Danh sách các enrollment cần liên hệ
     */
    public function scopeNeedsContact($query)
    {
        return $query->where('status', self::STATUS_WAITING)
            ->whereNull('notes');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Date;
use App\Enums\EnrollmentStatus;
use App\Models\LearningPathProgress;

class Enrollment extends Model
{
    use HasFactory, Date, SoftDeletes;

    protected $fillable = [
        'student_id',
        'course_item_id',
        'enrollment_date',
        'status',
        'request_date',
        'confirmation_date',
        'last_status_change',
        'previous_status',
        'cancelled_at',
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
        'cancelled_at' => 'datetime',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_fee' => 'decimal:2',
        'custom_fields' => 'array',
        'status' => EnrollmentStatus::class
    ];

    /**
     * Default attributes
     */
    protected $attributes = [
        'status' => 'active'
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
     * Lấy tổng số tiền đã thanh toán
     */
    public function getTotalPaidAmount()
    {
        return $this->payments()->where('status', 'confirmed')->sum('amount');
    }

    /**
     * Format ngày ghi danh theo định dạng dd/mm/yyyy
     */
    public function getFormattedEnrollmentDateAttribute()
    {
        return $this->formatDate('enrollment_date');
    }
    
    /**
     * Format ngày yêu cầu theo định dạng dd/mm/yyyy H:i:s
     */
    public function getFormattedRequestDateAttribute()
    {
        return $this->formatDateTime('request_date');
    }
    
    /**
     * Format ngày xác nhận theo định dạng dd/mm/yyyy H:i:s
     */
    public function getFormattedConfirmationDateAttribute()
    {
        return $this->formatDateTime('confirmation_date');
    }
    
    /**
     * Chuyển đổi ngày ghi danh từ định dạng dd/mm/yyyy sang Y-m-d khi gán giá trị
     */
    public function setEnrollmentDateAttribute($value)
    {
        $this->attributes['enrollment_date'] = static::parseDate($value);
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
     * Lấy trạng thái dưới dạng enum
     */
    public function getStatusEnum(): ?EnrollmentStatus
    {
        // Status đã được cast thành EnrollmentStatus, không cần convert
        return $this->status instanceof EnrollmentStatus ? $this->status : EnrollmentStatus::fromString($this->status);
    }

    /**
     * Lấy badge HTML cho trạng thái
     */
    public function getStatusBadgeAttribute(): string
    {
        $status = $this->getStatusEnum();
        return $status ? $status->badge() : '<span class="badge bg-secondary">' . $this->status . '</span>';
    }

    /**
     * Kiểm tra xem ghi danh có đang trong danh sách chờ không
     */
    public function isWaiting()
    {
        return $this->status === EnrollmentStatus::WAITING->value;
    }

    /**
     * Cập nhật trạng thái ghi danh
     */
    public function updateStatus($newStatus)
    {
        $this->previous_status = $this->status;
        $this->status = $newStatus;
        $this->last_status_change = now();
        
        if ($newStatus === EnrollmentStatus::ACTIVE->value) {
            $this->confirmation_date = now();
        }
        
        return $this->save();
    }

    /**
     * Scope cho danh sách chờ
     */
    public function scopeWaitingList($query)
    {
        return $query->where('status', EnrollmentStatus::WAITING->value);
    }

    /**
     * Scope cho ghi danh đang hoạt động
     */
    public function scopeActive($query)
    {
        return $query->where('status', EnrollmentStatus::ACTIVE->value);
    }

    /**
     * Scope cho ghi danh đã hoàn thành
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', EnrollmentStatus::COMPLETED->value);
    }

    /**
     * Scope lọc theo khóa học
     */
    public function scopeByCourse($query, $courseId)
    {
        return $query->where('course_item_id', $courseId);
    }

    /**
     * Scope lọc theo học viên
     */
    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope lọc theo khoảng thời gian ghi danh
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('enrollment_date', [$startDate, $endDate]);
    }

    /**
     * Scope với thông tin thanh toán (optimized)
     */
    public function scopeWithPaymentInfo($query)
    {
        return $query->with(['payments' => function($q) {
            $q->where('status', 'confirmed')->orderBy('payment_date', 'desc');
        }]);
    }

    /**
     * Scope cho các enrollment cần liên hệ (chưa thanh toán đủ)
     */
    public function scopeNeedsContact($query)
    {
        return $query->whereHas('student', function($q) {
            // Logic để xác định học viên cần liên hệ
        })->active();
    }

}

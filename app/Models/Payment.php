<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Date;
use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;

class Payment extends Model
{
    use HasFactory, Date;

    protected $fillable = [
        'enrollment_id',
        'amount',
        'payment_date',
        'payment_method',
        'transaction_reference',
        'status',
        'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'status' => PaymentStatus::class,
        'payment_method' => PaymentMethod::class
    ];

    /**
     * Quan hệ với ghi danh
     */
    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Get the student that owns the payment.
     */
    public function student()
    {
        return $this->enrollment ? $this->enrollment->student : null;
    }

    /**
     * Get the course item for the payment.
     */
    public function courseItem()
    {
        return $this->enrollment ? $this->enrollment->courseItem : null;
    }

    /**
     * Scope cho các thanh toán đã xác nhận
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', PaymentStatus::CONFIRMED);
    }

    /**
     * Scope cho các thanh toán đang chờ xác nhận
     */
    public function scopePending($query)
    {
        return $query->where('status', PaymentStatus::PENDING);
    }

    /**
     * Scope cho các thanh toán đã hủy
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', PaymentStatus::CANCELLED);
    }

    /**
     * Scope cho các thanh toán đã hoàn tiền
     */
    public function scopeRefunded($query)
    {
        return $query->where('status', PaymentStatus::REFUNDED);
    }

    /**
     * Tạo mã biên lai thanh toán
     */
    public function getReceiptNumberAttribute()
    {
        return 'PMT-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Format ngày thanh toán theo định dạng dd/mm/yyyy
     */
    public function getFormattedPaymentDateAttribute()
    {
        return $this->formatDate('payment_date');
    }

    /**
     * Chuyển đổi ngày thanh toán từ định dạng dd/mm/yyyy sang Y-m-d khi gán giá trị
     */
    public function setPaymentDateAttribute($value)
    {
        $this->attributes['payment_date'] = static::parseDate($value);
    }

    /**
     * Lấy trạng thái dưới dạng enum
     */
    public function getStatusEnum(): ?PaymentStatus
    {
        return $this->status instanceof PaymentStatus ? $this->status : PaymentStatus::fromString($this->status);
    }

    /**
     * Lấy phương thức thanh toán dưới dạng enum
     */
    public function getPaymentMethodEnum(): ?PaymentMethod
    {
        return $this->payment_method instanceof PaymentMethod ? $this->payment_method : PaymentMethod::fromString($this->payment_method);
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
     * Lấy badge HTML cho phương thức thanh toán
     */
    public function getPaymentMethodBadgeAttribute(): string
    {
        $method = $this->getPaymentMethodEnum();
        return $method ? $method->badge() : '<span class="badge bg-secondary">' . $this->payment_method . '</span>';
    }
}

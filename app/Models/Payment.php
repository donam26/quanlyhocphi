<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Date;
use App\Traits\Auditable;

class Payment extends Model
{
    use HasFactory, Date, Auditable;

    protected $fillable = [
        'enrollment_id',
        'amount',
        'payment_date',
        'payment_method',
        'transaction_reference',
        'idempotency_key',
        'status',
        'confirmed_at',
        'confirmed_by',
        'cancelled_at',
        'cancelled_by',
        'webhook_id',
        'webhook_data',
        'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'webhook_data' => 'array'
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
        return $query->where('status', 'confirmed');
    }

    /**
     * Scope cho các thanh toán đang chờ xác nhận
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope cho các thanh toán đã hủy
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
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
     * Confirm payment with audit logging
     */
    public function confirm($userId = null, array $metadata = [])
    {
        if ($this->status === 'confirmed') {
            return false; // Already confirmed
        }

        $this->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'confirmed_by' => $userId ?? auth()->id()
        ]);

        $this->auditAction('confirmed', array_merge($metadata, [
            'confirmed_by' => $userId ?? auth()->id(),
            'confirmed_at' => now()->toISOString()
        ]));

        return true;
    }

    /**
     * Cancel payment with audit logging
     */
    public function cancel($reason = null, $userId = null)
    {
        if ($this->status === 'cancelled') {
            return false; // Already cancelled
        }

        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => $userId ?? auth()->id(),
            'notes' => $this->notes . ($reason ? "\nHủy: {$reason}" : "\nĐã hủy thanh toán")
        ]);

        $this->auditAction('cancelled', [
            'reason' => $reason,
            'cancelled_by' => $userId ?? auth()->id(),
            'cancelled_at' => now()->toISOString()
        ]);

        return true;
    }

    /**
     * Get audit related info for logging
     */
    public function getAuditRelatedInfo()
    {
        $info = [];

        if ($this->enrollment) {
            $info['enrollment_id'] = $this->enrollment->id;

            if ($this->enrollment->student) {
                $info['student'] = [
                    'id' => $this->enrollment->student->id,
                    'name' => $this->enrollment->student->full_name,
                    'phone' => $this->enrollment->student->phone
                ];
            }

            if ($this->enrollment->courseItem) {
                $info['course'] = [
                    'id' => $this->enrollment->courseItem->id,
                    'name' => $this->enrollment->courseItem->name
                ];
            }
        }

        return $info;
    }
}

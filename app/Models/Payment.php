<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

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
        'payment_date' => 'date'
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
}

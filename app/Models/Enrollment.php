<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'course_class_id',
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
     * Quan hệ với lớp học
     */
    public function courseClass()
    {
        return $this->belongsTo(CourseClass::class);
    }

    /**
     * Quan hệ với các thanh toán
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Quan hệ với điểm danh
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Tính tổng số tiền đã đóng
     */
    public function getTotalPaidAmount()
    {
        return $this->payments()->where('status', 'confirmed')->sum('amount');
    }

    /**
     * Tính số tiền còn thiếu
     */
    public function getRemainingAmount()
    {
        return $this->final_fee - $this->getTotalPaidAmount();
    }

    /**
     * Kiểm tra đã đóng đủ học phí chưa
     */
    public function hasFullyPaid()
    {
        return $this->getRemainingAmount() <= 0;
    }

    /**
     * Scope cho các ghi danh đang hoạt động
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'enrolled');
    }

    /**
     * Scope cho các ghi danh chưa đóng đủ học phí
     */
    public function scopeUnpaid($query)
    {
        return $query->whereHas('payments', function($q) {
            $q->selectRaw('enrollment_id, SUM(amount) as total_paid')
              ->where('status', 'confirmed')
              ->groupBy('enrollment_id')
              ->havingRaw('total_paid < final_fee');
        });
    }
}

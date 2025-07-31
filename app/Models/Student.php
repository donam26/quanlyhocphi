<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'date_of_birth',
        'gender',
        'email',
        'phone',
        'address',
        'current_workplace',
        'accounting_experience_years',
        'status',
        'notes',
        'custom_fields'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'custom_fields' => 'array'
    ];

    /**
     * Quan hệ với các ghi danh
     */
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Quan hệ với các khóa học thông qua ghi danh
     */
    public function courseItems()
    {
        return $this->belongsToMany(CourseItem::class, 'enrollments', 'student_id', 'course_item_id')
                    ->withPivot('enrollment_date', 'status', 'discount_percentage', 'discount_amount', 'final_fee')
                    ->withTimestamps();
    }

    /**
     * Lấy danh sách chờ của học viên
     */
    public function waitingLists()
    {
        return $this->hasMany(Enrollment::class)
            ->where('status', 'waiting');
    }

    /**
     * Quan hệ với các thanh toán thông qua ghi danh
     */
    public function payments()
    {
        return $this->hasManyThrough(Payment::class, Enrollment::class);
    }

    /**
     * Quan hệ với điểm danh thông qua ghi danh
     */
    public function attendances()
    {
        return $this->hasManyThrough(Attendance::class, Enrollment::class);
    }

    /**
     * Lấy các ghi danh đang hoạt động
     */
    public function activeEnrollments()
    {
        return $this->enrollments()->where('status', 'enrolled');
    }

    /**
     * Tính tổng số tiền đã đóng
     */
    public function getTotalPaidAmount()
    {
        return $this->payments()->where('status', 'confirmed')->sum('amount');
    }

    /**
     * Tính tổng học phí cần đóng
     */
    public function getTotalFeeAmount()
    {
        return $this->enrollments()->where('status', 'enrolled')->sum('final_fee');
    }

    /**
     * Tính số tiền còn thiếu
     */
    public function getRemainingAmount()
    {
        return $this->getTotalFeeAmount() - $this->getTotalPaidAmount();
    }

    /**
     * Kiểm tra đã đóng đủ học phí chưa
     */
    public function hasFullyPaid()
    {
        return $this->getRemainingAmount() <= 0;
    }

    /**
     * Scope tìm kiếm theo tên hoặc số điện thoại
     */
    public function scopeSearch($query, $term)
    {
        if (preg_match('/^\d+$/', $term)) {
            // Nếu term chỉ chứa số, tìm kiếm chính xác theo số điện thoại
            return $query->where('phone', 'like', "%{$term}%");
        } else {
            // Ngược lại, tìm kiếm theo tên hoặc số điện thoại
            return $query->where('full_name', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");
        }
    }
}

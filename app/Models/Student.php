<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Date;
use App\Enums\StudentSource;

class Student extends Model
{
    use HasFactory, Date, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'email',
        'phone',
        'citizen_id',
        'province_id',
        'place_of_birth_province_id',
        'nation',
        'ethnicity_id',
        'current_workplace',
        'accounting_experience_years',
        'notes',
        'hard_copy_documents',
        'education_level',
        'training_specialization',
        'user_note_id',
        'company_name',
        'tax_code',
        'invoice_email',
        'company_address',
        'source'
    ];

    /**
     * Các accessor sẽ được append vào JSON response
     */
    protected $appends = [
        'full_name',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'hard_copy_documents' => 'string',
        'education_level' => 'string',
        'source' => StudentSource::class,
    ];

    /**
     * Quan hệ với tỉnh thành hiện tại
     */
    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    /**
     * Quan hệ với tỉnh thành nơi sinh
     */
    public function placeOfBirthProvince()
    {
        return $this->belongsTo(Province::class, 'place_of_birth_province_id');
    }

    /**
     * Quan hệ với dân tộc
     */
    public function ethnicity()
    {
        return $this->belongsTo(\App\Models\Ethnicity::class, 'ethnicity_id');
    }

    /**
     * Lấy miền (khu vực) của học viên thông qua province
     */
    public function getRegionAttribute()
    {
        return $this->province ? $this->province->region : 'unknown';
    }

    /**
     * Lấy tên miền (khu vực) của học viên thông qua province
     */
    public function getRegionNameAttribute()
    {
        return $this->province ? $this->province->region_name : 'Không xác định';
    }

    /**
     * Format ngày sinh theo định dạng dd/mm/yyyy
     */
    public function getFormattedDateOfBirthAttribute()
    {
        return $this->formatDate('date_of_birth');
    }

    /**
     * Chuyển đổi ngày sinh từ định dạng dd/mm/yyyy sang Y-m-d khi gán giá trị
     */
    public function setDateOfBirthAttribute($value)
    {
        $this->attributes['date_of_birth'] = static::parseDate($value);
    }

    /**
     * Accessor để lấy full_name từ first_name + last_name
     */

    /**
     * Mutator để tách full_name thành first_name và last_name khi assign
     */
    public function setFullNameAttribute($value)
    {
        if ($value) {
            $parts = explode(' ', trim($value));
            
            if (count($parts) > 1) {
                $this->attributes['last_name'] = array_pop($parts);
                $this->attributes['first_name'] = implode(' ', $parts);
            } else {
                $this->attributes['first_name'] = '';
                $this->attributes['last_name'] = $value;
            }
        }
    }

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
            ->where('enrollments.status', 'waiting');
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
        return $this->enrollments()->where('enrollments.status', 'active');
    }

    /**
     * Tính tổng số tiền đã đóng
     */
    public function getTotalPaidAmount()
    {
        return $this->payments()->where('payments.status', 'confirmed')->sum('amount');
    }

    /**
     * Tính tổng học phí cần đóng
     */
    public function getTotalFeeAmount()
    {
        return $this->enrollments()->where('enrollments.status', \App\Enums\EnrollmentStatus::ACTIVE->value)->sum('final_fee');
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
     * Accessor: Tự động tạo full_name từ first_name + last_name
     */
    public function getFullNameAttribute()
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    /**
     * Scope tìm kiếm theo tên hoặc số điện thoại (Improved version)
     */
    public function scopeSearch($query, $term)
    {
        if (empty($term)) {
            return $query;
        }

        // Trim and sanitize the search term
        $term = trim($term);

        return $query->where(function ($q) use ($term) {
            // Nếu term chỉ chứa số, ưu tiên tìm kiếm theo SĐT hoặc CCCD
            if (preg_match('/^\d+$/', $term)) {
                $q->where('phone', 'like', "%{$term}%")
                  ->orWhere('citizen_id', 'like', "%{$term}%");
            }
            
            // Nếu term chứa @ thì tìm kiếm email
            if (strpos($term, '@') !== false) {
                $q->orWhere('email', 'like', "%{$term}%");
            }

            // Tìm kiếm trong họ tên - sử dụng LIKE đơn giản, không dùng BINARY
            // Hỗ trợ tìm kiếm tiếng Việt có dấu tự nhiên
            $q->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$term}%"])
              ->orWhere('first_name', 'like', "%{$term}%")
              ->orWhere('last_name', 'like', "%{$term}%");
              
            // Fallback: tìm kiếm không phân biệt hoa thường
            $q->orWhereRaw("LOWER(CONCAT(first_name, ' ', last_name)) LIKE LOWER(?)", ["%{$term}%"])
              ->orWhereRaw("LOWER(first_name) LIKE LOWER(?)", ["%{$term}%"])
              ->orWhereRaw("LOWER(last_name) LIKE LOWER(?)", ["%{$term}%"]);
              
            // Tìm kiếm thêm trong phone và email với OR logic
            $q->orWhere('phone', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%");
        });
    }

    /**
     * Scope tìm kiếm nâng cao với fulltext
     */
    public function scopeFulltextSearch($query, $term)
    {
        if (empty($term) || strlen($term) < 3) {
            return $query;
        }

        return $query->whereRaw("MATCH(first_name, last_name, email, phone) AGAINST(? IN BOOLEAN MODE)", ["+{$term}*"]);
    }

    /**
     * Scope tìm kiếm thông minh - kết hợp nhiều phương pháp
     */
    public function scopeSmartSearch($query, $term)
    {
        if (empty($term)) {
            return $query;
        }

        $term = trim($term);

        // Nếu term ngắn (< 3 ký tự), chỉ dùng LIKE search
        if (strlen($term) < 3) {
            return $query->search($term);
        }

        // Kết hợp cả fulltext và LIKE search
        return $query->where(function ($q) use ($term) {
            // Thử fulltext search trước (nhanh hơn)
            $q->whereRaw("MATCH(first_name, last_name, email, phone) AGAINST(? IN BOOLEAN MODE)", ["+{$term}*"]);

            // Fallback về LIKE search nếu fulltext không có kết quả
            $q->orWhere(function ($subQuery) use ($term) {
                $subQuery->search($term);
            });
        });
    }

    /**
     * Scope tìm kiếm theo nơi làm việc
     */
    public function scopeSearchWorkplace($query, $term)
    {
        if (empty($term)) {
            return $query;
        }

        if (strlen($term) >= 3) {
            return $query->whereRaw("MATCH(current_workplace, training_specialization) AGAINST(? IN BOOLEAN MODE)", ["+{$term}*"]);
        }

        return $query->where('current_workplace', 'like', "%{$term}%");
    }

    /**
     * Lấy nguồn dưới dạng enum
     */
    public function getSourceEnum(): ?StudentSource
    {
        if ($this->source instanceof StudentSource) {
            return $this->source;
        }

        if (is_string($this->source)) {
            return StudentSource::fromString($this->source);
        }

        return null;
    }

    /**
     * Lấy badge HTML cho nguồn
     */
    public function getSourceBadgeAttribute(): string
    {
        $source = $this->getSourceEnum();
        return $source ? $source->badge() : '<span class="badge bg-secondary">Chưa xác định</span>';
    }

    /**
     * Scope lọc theo nguồn
     */
    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }
}

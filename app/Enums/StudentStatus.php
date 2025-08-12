<?php

namespace App\Enums;

enum StudentStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case GRADUATED = 'graduated';
    case SUSPENDED = 'suspended';

    /**
     * Lấy tên hiển thị của trạng thái học viên
     */
    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Đang học',
            self::INACTIVE => 'Tạm nghỉ',
            self::GRADUATED => 'Đã tốt nghiệp',
            self::SUSPENDED => 'Đình chỉ',
        };
    }

    /**
     * Lấy màu badge của trạng thái
     */
    public function color(): string
    {
        return match($this) {
            self::ACTIVE => 'success',
            self::INACTIVE => 'warning',
            self::GRADUATED => 'primary',
            self::SUSPENDED => 'danger',
        };
    }

    /**
     * Lấy icon của trạng thái
     */
    public function icon(): string
    {
        return match($this) {
            self::ACTIVE => 'fas fa-user-check',
            self::INACTIVE => 'fas fa-user-clock',
            self::GRADUATED => 'fas fa-graduation-cap',
            self::SUSPENDED => 'fas fa-user-times',
        };
    }

    /**
     * Lấy danh sách tất cả giá trị enum dưới dạng mảng
     */
    public static function toArray(): array
    {
        return [
            self::ACTIVE->value => self::ACTIVE->label(),
            self::INACTIVE->value => self::INACTIVE->label(),
            self::GRADUATED->value => self::GRADUATED->label(),
            self::SUSPENDED->value => self::SUSPENDED->label(),
        ];
    }

    /**
     * Tạo badge HTML cho trạng thái
     */
    public function badge(): string
    {
        return '<span class="badge bg-' . $this->color() . '"><i class="' . $this->icon() . ' me-1"></i>' . $this->label() . '</span>';
    }

    /**
     * Chuyển đổi từ string sang enum
     */
    public static function fromString(?string $value): ?self
    {
        if ($value === null) return null;
        
        return match($value) {
            'active' => self::ACTIVE,
            'inactive' => self::INACTIVE,
            'graduated' => self::GRADUATED,
            'suspended' => self::SUSPENDED,
            default => null,
        };
    }

    /**
     * Kiểm tra xem học viên có đang hoạt động không
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Kiểm tra xem học viên có thể đăng ký khóa học mới không
     */
    public function canEnroll(): bool
    {
        return in_array($this, [self::ACTIVE, self::INACTIVE]);
    }
}

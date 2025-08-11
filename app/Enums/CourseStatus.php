<?php

namespace App\Enums;

enum CourseStatus: string
{
    case ACTIVE = 'active';
    case COMPLETED = 'completed';

    /**
     * Lấy tên hiển thị của trạng thái khóa học
     */
    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Đang học',
            self::COMPLETED => 'Đã kết thúc',
        };
    }

    /**
     * Lấy màu badge của trạng thái
     */
    public function color(): string
    {
        return match($this) {
            self::ACTIVE => 'success',
            self::COMPLETED => 'secondary',
        };
    }

    /**
     * Lấy icon của trạng thái
     */
    public function icon(): string
    {
        return match($this) {
            self::ACTIVE => 'fas fa-play',
            self::COMPLETED => 'fas fa-check',
        };
    }

    /**
     * Lấy danh sách tất cả giá trị enum dưới dạng mảng
     */
    public static function toArray(): array
    {
        return [
            self::ACTIVE->value => self::ACTIVE->label(),
            self::COMPLETED->value => self::COMPLETED->label(),
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
            'completed' => self::COMPLETED,
            default => null,
        };
    }
}

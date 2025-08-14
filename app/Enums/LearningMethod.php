<?php

namespace App\Enums;

enum LearningMethod: string
{
    case ONLINE = 'online';
    case OFFLINE = 'offline';

    /**
     * Lấy tên hiển thị của phương thức học
     */
    public function label(): string
    {
        return match($this) {
            self::ONLINE => 'Trực tuyến',
            self::OFFLINE => 'Trực tiếp',
        };
    }

    /**
     * Lấy màu badge của phương thức học
     */
    public function color(): string
    {
        return match($this) {
            self::ONLINE => 'info',
            self::OFFLINE => 'primary',
        };
    }

    /**
     * Lấy icon của phương thức học
     */
    public function icon(): string
    {
        return match($this) {
            self::ONLINE => 'fas fa-laptop',
            self::OFFLINE => 'fas fa-chalkboard-teacher',
        };
    }

    /**
     * Lấy danh sách tất cả giá trị enum dưới dạng mảng
     */
    public static function toArray(): array
    {
        return [
            self::ONLINE->value => self::ONLINE->label(),
            self::OFFLINE->value => self::OFFLINE->label(),
        ];
    }

    /**
     * Tạo badge HTML cho phương thức học
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
            'online' => self::ONLINE,
            'offline' => self::OFFLINE,
            default => null,
        };
    }
}

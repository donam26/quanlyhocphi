<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case WAITING = 'waiting';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    /**
     * Lấy tên hiển thị của trạng thái
     */
    public function label(): string
    {
        return match($this) {
            self::WAITING => 'Danh sách chờ',
            self::ACTIVE => 'Đang học',
            self::COMPLETED => 'Đã hoàn thành',
            self::CANCELLED => 'Đã hủy',
        };
    }

    /**
     * Lấy màu badge của trạng thái
     */
    public function color(): string
    {
        return match($this) {
            self::WAITING => 'warning text-dark',
            self::ACTIVE => 'success',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
        };
    }

    /**
     * Lấy danh sách tất cả giá trị enum dưới dạng mảng
     */
    public static function toArray(): array
    {
        return [
            self::WAITING->value => self::WAITING->label(),
            self::ACTIVE->value => self::ACTIVE->label(),
            self::COMPLETED->value => self::COMPLETED->label(),
            self::CANCELLED->value => self::CANCELLED->label(),
        ];
    }

    /**
     * Tạo badge HTML cho trạng thái
     */
    public function badge(): string
    {
        return '<span class="badge bg-' . $this->color() . '">' . $this->label() . '</span>';
    }

    /**
     * Chuyển đổi từ string sang enum
     */
    public static function fromString(?string $value): ?self
    {
        if ($value === null) return null;
        
        return match($value) {
            'waiting' => self::WAITING,
            'active', 'enrolled' => self::ACTIVE, // Hỗ trợ cả giá trị 'enrolled' cũ
            'completed' => self::COMPLETED,
            'cancelled' => self::CANCELLED,
            default => null,
        };
    }
} 
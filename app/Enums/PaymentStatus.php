<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    /**
     * Lấy tên hiển thị của trạng thái thanh toán
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Chờ xác nhận',
            self::CONFIRMED => 'Đã xác nhận',
            self::CANCELLED => 'Đã hủy',
            self::REFUNDED => 'Đã hoàn tiền',
        };
    }

    /**
     * Lấy màu badge của trạng thái
     */
    public function color(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::CONFIRMED => 'success',
            self::CANCELLED => 'danger',
            self::REFUNDED => 'secondary',
        };
    }

    /**
     * Lấy icon của trạng thái
     */
    public function icon(): string
    {
        return match($this) {
            self::PENDING => 'fas fa-clock',
            self::CONFIRMED => 'fas fa-check',
            self::CANCELLED => 'fas fa-times',
            self::REFUNDED => 'fas fa-undo',
        };
    }

    /**
     * Lấy danh sách tất cả giá trị enum dưới dạng mảng
     */
    public static function toArray(): array
    {
        return [
            self::PENDING->value => self::PENDING->label(),
            self::CONFIRMED->value => self::CONFIRMED->label(),
            self::CANCELLED->value => self::CANCELLED->label(),
            self::REFUNDED->value => self::REFUNDED->label(),
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
            'pending' => self::PENDING,
            'confirmed' => self::CONFIRMED,
            'cancelled' => self::CANCELLED,
            'refunded' => self::REFUNDED,
            default => null,
        };
    }

    /**
     * Kiểm tra xem có phải trạng thái đã hoàn thành không
     */
    public function isCompleted(): bool
    {
        return $this === self::CONFIRMED;
    }

    /**
     * Kiểm tra xem có thể hủy được không
     */
    public function canCancel(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * Kiểm tra xem có thể hoàn tiền được không
     */
    public function canRefund(): bool
    {
        return $this === self::CONFIRMED;
    }
}

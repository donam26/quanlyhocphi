<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case BANK_TRANSFER = 'bank_transfer';
    case CARD = 'card';
    case QR_CODE = 'qr_code';
    case SEPAY = 'sepay';
    case OTHER = 'other';

    /**
     * Lấy tên hiển thị của phương thức thanh toán
     */
    public function label(): string
    {
        return match($this) {
            self::CASH => 'Tiền mặt',
            self::BANK_TRANSFER => 'Chuyển khoản',
            self::CARD => 'Thẻ',
            self::QR_CODE => 'Mã QR',
            self::SEPAY => 'SePay',
            self::OTHER => 'Khác',
        };
    }

    /**
     * Lấy icon của phương thức thanh toán
     */
    public function icon(): string
    {
        return match($this) {
            self::CASH => 'fas fa-money-bill',
            self::BANK_TRANSFER => 'fas fa-university',
            self::CARD => 'fas fa-credit-card',
            self::QR_CODE => 'fas fa-qrcode',
            self::SEPAY => 'fas fa-mobile-alt',
            self::OTHER => 'fas fa-ellipsis-h',
        };
    }

    /**
     * Lấy màu cho phương thức thanh toán
     */
    public function color(): string
    {
        return match($this) {
            self::CASH => 'success',
            self::BANK_TRANSFER => 'primary',
            self::CARD => 'info',
            self::QR_CODE => 'warning',
            self::SEPAY => 'danger',
            self::OTHER => 'secondary',
        };
    }

    /**
     * Lấy danh sách tất cả giá trị enum dưới dạng mảng
     */
    public static function toArray(): array
    {
        return [
            self::CASH->value => self::CASH->label(),
            self::BANK_TRANSFER->value => self::BANK_TRANSFER->label(),
            self::CARD->value => self::CARD->label(),
            self::QR_CODE->value => self::QR_CODE->label(),
            self::SEPAY->value => self::SEPAY->label(),
            self::OTHER->value => self::OTHER->label(),
        ];
    }

    /**
     * Tạo badge HTML cho phương thức thanh toán
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
            'cash' => self::CASH,
            'bank_transfer' => self::BANK_TRANSFER,
            'card' => self::CARD,
            'qr_code' => self::QR_CODE,
            'sepay' => self::SEPAY,
            'other' => self::OTHER,
            default => null,
        };
    }

    /**
     * Kiểm tra xem có phải phương thức thanh toán trực tuyến không
     */
    public function isOnline(): bool
    {
        return in_array($this, [self::BANK_TRANSFER, self::CARD, self::QR_CODE, self::SEPAY]);
    }

    /**
     * Kiểm tra xem có phải phương thức thanh toán offline không
     */
    public function isOffline(): bool
    {
        return in_array($this, [self::CASH, self::OTHER]);
    }
}

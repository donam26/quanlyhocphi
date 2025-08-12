<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case PRESENT = 'present';
    case ABSENT = 'absent';
    case LATE = 'late';
    case EXCUSED = 'excused';

    /**
     * Lấy tên hiển thị của trạng thái điểm danh
     */
    public function label(): string
    {
        return match($this) {
            self::PRESENT => 'Có mặt',
            self::ABSENT => 'Vắng mặt',
            self::LATE => 'Đi muộn',
            self::EXCUSED => 'Có phép',
        };
    }

    /**
     * Lấy màu badge của trạng thái
     */
    public function color(): string
    {
        return match($this) {
            self::PRESENT => 'success',
            self::ABSENT => 'danger',
            self::LATE => 'warning',
            self::EXCUSED => 'info',
        };
    }

    /**
     * Lấy icon của trạng thái
     */
    public function icon(): string
    {
        return match($this) {
            self::PRESENT => 'fas fa-check',
            self::ABSENT => 'fas fa-times',
            self::LATE => 'fas fa-clock',
            self::EXCUSED => 'fas fa-info',
        };
    }

    /**
     * Lấy danh sách tất cả giá trị enum dưới dạng mảng
     */
    public static function toArray(): array
    {
        return [
            self::PRESENT->value => self::PRESENT->label(),
            self::ABSENT->value => self::ABSENT->label(),
            self::LATE->value => self::LATE->label(),
            self::EXCUSED->value => self::EXCUSED->label(),
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
            'present' => self::PRESENT,
            'absent' => self::ABSENT,
            'late' => self::LATE,
            'excused' => self::EXCUSED,
            default => null,
        };
    }

    /**
     * Kiểm tra xem có được tính là có mặt không
     */
    public function isPresent(): bool
    {
        return in_array($this, [self::PRESENT, self::LATE]);
    }

    /**
     * Kiểm tra xem có được tính là vắng mặt không
     */
    public function isAbsent(): bool
    {
        return $this === self::ABSENT;
    }
}

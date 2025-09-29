<?php

namespace App\Enums;

enum EducationLevel: string
{
    case VOCATIONAL = 'vocational';
    case ASSOCIATE = 'associate';
    case BACHELOR = 'bachelor';
    case MASTER = 'master';
    case SECONDARY = 'secondary';
    case OTHER = 'other';

    /**
     * Lấy tên hiển thị của trình độ học vấn
     */
    public function label(): string
    {
        return match($this) {
            self::VOCATIONAL => 'Trung cấp',
            self::ASSOCIATE => 'Cao đẳng',
            self::BACHELOR => 'Đại học',
            self::MASTER => 'Thạc sĩ',
            self::SECONDARY => 'Trung học',
            self::OTHER => 'Khác',
        };
    }

    /**
     * Lấy màu badge của trình độ học vấn
     */
    public function color(): string
    {
        return match($this) {
            self::VOCATIONAL => 'info',
            self::ASSOCIATE => 'primary',
            self::BACHELOR => 'success',
            self::MASTER => 'warning',
            self::SECONDARY => 'secondary',
            self::OTHER => 'light',
        };
    }

    /**
     * Lấy icon của trình độ học vấn
     */
    public function icon(): string
    {
        return match($this) {
            self::VOCATIONAL => 'fas fa-tools',
            self::ASSOCIATE => 'fas fa-certificate',
            self::BACHELOR => 'fas fa-graduation-cap',
            self::MASTER => 'fas fa-user-graduate',
            self::SECONDARY => 'fas fa-school',
            self::OTHER => 'fas fa-question-circle',
        };
    }

    /**
     * Lấy danh sách tất cả giá trị enum dưới dạng mảng
     */
    public static function toArray(): array
    {
        return [
            self::VOCATIONAL->value => self::VOCATIONAL->label(),
            self::ASSOCIATE->value => self::ASSOCIATE->label(),
            self::BACHELOR->value => self::BACHELOR->label(),
            self::MASTER->value => self::MASTER->label(),
            self::SECONDARY->value => self::SECONDARY->label(),
            self::OTHER->value => self::OTHER->label(),
        ];
    }

    /**
     * Tạo badge HTML cho trình độ học vấn
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
            'vocational' => self::VOCATIONAL,
            'associate' => self::ASSOCIATE,
            'bachelor' => self::BACHELOR,
            'master' => self::MASTER,
            'secondary' => self::SECONDARY,
            'other' => self::OTHER,
            default => null,
        };
    }

    /**
     * Lấy danh sách cho select options
     */
    public static function getSelectOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[] = [
                'value' => $case->value,
                'label' => $case->label(),
                'icon' => $case->icon(),
                'color' => $case->color()
            ];
        }
        return $options;
    }
}

<?php

namespace App\Enums;

enum StudentSource: string
{
    case FACEBOOK = 'facebook';
    case ZALO = 'zalo';
    case WEBSITE = 'website';
    case LINKEDIN = 'linkedin';
    case TIKTOK = 'tiktok';
    case FRIEND_REFERRAL = 'friend_referral';
    case OTHER = 'other';

    /**
     * Lấy tên hiển thị của nguồn
     */
    public function label(): string
    {
        return match($this) {
            self::FACEBOOK => 'Facebook',
            self::ZALO => 'Zalo',
            self::WEBSITE => 'Website',
            self::LINKEDIN => 'LinkedIn',
            self::TIKTOK => 'TikTok',
            self::FRIEND_REFERRAL => 'Bạn bè giới thiệu',
            self::OTHER => 'Khác',
        };
    }

    /**
     * Lấy màu badge của nguồn
     */
    public function color(): string
    {
        return match($this) {
            self::FACEBOOK => 'primary',
            self::ZALO => 'info',
            self::WEBSITE => 'success',
            self::LINKEDIN => 'secondary',
            self::TIKTOK => 'dark',
            self::FRIEND_REFERRAL => 'warning',
            self::OTHER => 'light',
        };
    }

    /**
     * Lấy icon của nguồn
     */
    public function icon(): string
    {
        return match($this) {
            self::FACEBOOK => 'fab fa-facebook',
            self::ZALO => 'fas fa-comments',
            self::WEBSITE => 'fas fa-globe',
            self::LINKEDIN => 'fab fa-linkedin',
            self::TIKTOK => 'fab fa-tiktok',
            self::FRIEND_REFERRAL => 'fas fa-users',
            self::OTHER => 'fas fa-question-circle',
        };
    }

    /**
     * Lấy danh sách tất cả giá trị enum dưới dạng mảng
     */
    public static function toArray(): array
    {
        return [
            self::FACEBOOK->value => self::FACEBOOK->label(),
            self::ZALO->value => self::ZALO->label(),
            self::WEBSITE->value => self::WEBSITE->label(),
            self::LINKEDIN->value => self::LINKEDIN->label(),
            self::TIKTOK->value => self::TIKTOK->label(),
            self::FRIEND_REFERRAL->value => self::FRIEND_REFERRAL->label(),
            self::OTHER->value => self::OTHER->label(),
        ];
    }

    /**
     * Tạo badge HTML cho nguồn
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
            'facebook' => self::FACEBOOK,
            'zalo' => self::ZALO,
            'website' => self::WEBSITE,
            'linkedin' => self::LINKEDIN,
            'tiktok' => self::TIKTOK,
            'friend_referral' => self::FRIEND_REFERRAL,
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

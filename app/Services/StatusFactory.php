<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\AttendanceStatus;
use App\Enums\PaymentMethod;
use App\Enums\StudentStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\CourseStatus;

/**
 * StatusFactory - Factory Pattern để tạo status objects và badges
 * Tuân thủ Open/Closed Principle và Factory Pattern
 */
class StatusFactory
{
    /**
     * Mapping các loại status với enum tương ứng
     */
    private static array $statusTypes = [
        'payment' => PaymentStatus::class,
        'attendance' => AttendanceStatus::class,
        'payment_method' => PaymentMethod::class,
        'student' => StudentStatus::class,
        'enrollment' => EnrollmentStatus::class,
        'course' => CourseStatus::class,
    ];

    /**
     * Tạo status object từ string value
     * 
     * @param string $type Loại status (payment, attendance, etc.)
     * @param string|null $value Giá trị status
     * @return object|null Status enum object
     */
    public static function create(string $type, ?string $value): ?object
    {
        if (!isset(self::$statusTypes[$type]) || $value === null) {
            return null;
        }

        $enumClass = self::$statusTypes[$type];
        
        if (method_exists($enumClass, 'fromString')) {
            return $enumClass::fromString($value);
        }

        // Fallback cho enum cơ bản
        return self::tryCreateEnum($enumClass, $value);
    }

    /**
     * Tạo badge HTML cho status
     * 
     * @param string $type Loại status
     * @param string|null $value Giá trị status
     * @return string HTML badge
     */
    public static function createBadge(string $type, ?string $value): string
    {
        $status = self::create($type, $value);
        
        if ($status && method_exists($status, 'badge')) {
            return $status->badge();
        }

        // Fallback badge
        return self::createFallbackBadge($value);
    }

    /**
     * Lấy label của status
     * 
     * @param string $type Loại status
     * @param string|null $value Giá trị status
     * @return string Label
     */
    public static function getLabel(string $type, ?string $value): string
    {
        $status = self::create($type, $value);
        
        if ($status && method_exists($status, 'label')) {
            return $status->label();
        }

        return $value ?? 'Không xác định';
    }

    /**
     * Lấy màu của status
     * 
     * @param string $type Loại status
     * @param string|null $value Giá trị status
     * @return string Color class
     */
    public static function getColor(string $type, ?string $value): string
    {
        $status = self::create($type, $value);
        
        if ($status && method_exists($status, 'color')) {
            return $status->color();
        }

        return 'secondary';
    }

    /**
     * Lấy icon của status
     * 
     * @param string $type Loại status
     * @param string|null $value Giá trị status
     * @return string Icon class
     */
    public static function getIcon(string $type, ?string $value): string
    {
        $status = self::create($type, $value);
        
        if ($status && method_exists($status, 'icon')) {
            return $status->icon();
        }

        return 'fas fa-circle';
    }

    /**
     * Lấy tất cả options cho một loại status (dùng cho select dropdown)
     * 
     * @param string $type Loại status
     * @return array Array of [value => label]
     */
    public static function getOptions(string $type): array
    {
        if (!isset(self::$statusTypes[$type])) {
            return [];
        }

        $enumClass = self::$statusTypes[$type];
        
        if (method_exists($enumClass, 'toArray')) {
            return $enumClass::toArray();
        }

        // Fallback cho enum cơ bản
        return self::getEnumCases($enumClass);
    }

    /**
     * Kiểm tra status có hợp lệ không
     * 
     * @param string $type Loại status
     * @param string|null $value Giá trị status
     * @return bool
     */
    public static function isValid(string $type, ?string $value): bool
    {
        return self::create($type, $value) !== null;
    }

    /**
     * Lấy tất cả status types có sẵn
     * 
     * @return array
     */
    public static function getAvailableTypes(): array
    {
        return array_keys(self::$statusTypes);
    }

    /**
     * Tạo status collection cho JavaScript
     * 
     * @param string $type Loại status
     * @return array
     */
    public static function createJavaScriptCollection(string $type): array
    {
        if (!isset(self::$statusTypes[$type])) {
            return [];
        }

        $enumClass = self::$statusTypes[$type];
        $collection = [];

        if (method_exists($enumClass, 'cases')) {
            foreach ($enumClass::cases() as $case) {
                $collection[$case->value] = [
                    'value' => $case->value,
                    'label' => method_exists($case, 'label') ? $case->label() : $case->value,
                    'color' => method_exists($case, 'color') ? $case->color() : 'secondary',
                    'icon' => method_exists($case, 'icon') ? $case->icon() : 'fas fa-circle',
                    'badge' => method_exists($case, 'badge') ? $case->badge() : self::createFallbackBadge($case->value)
                ];
            }
        }

        return $collection;
    }

    /**
     * Thử tạo enum từ class và value
     * 
     * @param string $enumClass
     * @param string $value
     * @return object|null
     */
    private static function tryCreateEnum(string $enumClass, string $value): ?object
    {
        if (method_exists($enumClass, 'cases')) {
            foreach ($enumClass::cases() as $case) {
                if ($case->value === $value) {
                    return $case;
                }
            }
        }

        return null;
    }

    /**
     * Tạo fallback badge
     * 
     * @param string|null $value
     * @return string
     */
    private static function createFallbackBadge(?string $value): string
    {
        $displayValue = $value ?? 'Không xác định';
        return '<span class="badge bg-secondary">' . htmlspecialchars($displayValue) . '</span>';
    }

    /**
     * Lấy enum cases cho fallback
     * 
     * @param string $enumClass
     * @return array
     */
    private static function getEnumCases(string $enumClass): array
    {
        $cases = [];
        
        if (method_exists($enumClass, 'cases')) {
            foreach ($enumClass::cases() as $case) {
                $cases[$case->value] = $case->value;
            }
        }

        return $cases;
    }

    /**
     * Đăng ký loại status mới
     * 
     * @param string $type
     * @param string $enumClass
     */
    public static function registerStatusType(string $type, string $enumClass): void
    {
        self::$statusTypes[$type] = $enumClass;
    }
}

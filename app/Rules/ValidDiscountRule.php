<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\CourseItem;

class ValidDiscountRule implements ValidationRule
{
    private $courseItemId;
    private $discountType; // 'percentage' or 'amount'

    /**
     * Create a new rule instance.
     *
     * @param int|null $courseItemId
     * @param string $discountType
     */
    public function __construct($courseItemId = null, $discountType = 'percentage')
    {
        $this->courseItemId = $courseItemId;
        $this->discountType = $discountType;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value) || $value == 0) {
            return; // Allow empty or zero values
        }

        // Get course item ID from request if not provided
        $courseItemId = $this->courseItemId ?? request('course_item_id');

        if (!$courseItemId) {
            return; // Let other validation handle missing course
        }

        $courseItem = CourseItem::find($courseItemId);
        if (!$courseItem) {
            return; // Let other validation handle invalid course
        }

        $originalFee = $courseItem->fee;

        if ($this->discountType === 'percentage') {
            // Validate percentage discount
            if ($value < 0 || $value > 100) {
                $fail('Phần trăm chiết khấu phải từ 0 đến 100.');
                return;
            }

            // Check if percentage results in negative final fee
            $discountAmount = $originalFee * ($value / 100);
            if ($discountAmount > $originalFee) {
                $fail('Phần trăm chiết khấu quá lớn, học phí cuối cùng sẽ âm.');
                return;
            }

            // Check maximum discount percentage if defined
            if (isset($courseItem->custom_fields['max_discount_percentage'])) {
                $maxDiscountPercentage = (float) $courseItem->custom_fields['max_discount_percentage'];
                if ($value > $maxDiscountPercentage) {
                    $fail("Phần trăm chiết khấu không được vượt quá {$maxDiscountPercentage}% cho khóa học này.");
                    return;
                }
            }

        } elseif ($this->discountType === 'amount') {
            // Validate amount discount
            if ($value < 0) {
                $fail('Số tiền chiết khấu không được âm.');
                return;
            }

            if ($value > $originalFee) {
                $fail('Số tiền chiết khấu không được lớn hơn học phí gốc.');
                return;
            }

            // Check maximum discount amount if defined
            if (isset($courseItem->custom_fields['max_discount_amount'])) {
                $maxDiscountAmount = (float) $courseItem->custom_fields['max_discount_amount'];
                if ($value > $maxDiscountAmount) {
                    $fail("Số tiền chiết khấu không được vượt quá " . number_format($maxDiscountAmount, 0, ',', '.') . " VND cho khóa học này.");
                    return;
                }
            }
        }

        // Calculate final fee considering both percentage and amount discounts
        $discountPercentage = request('discount_percentage', 0);
        $discountAmount = request('discount_amount', 0);

        // If validating percentage, use the new value
        if ($this->discountType === 'percentage') {
            $discountPercentage = $value;
        }
        // If validating amount, use the new value
        if ($this->discountType === 'amount') {
            $discountAmount = $value;
        }

        // The frontend synchronizes percentage and amount, so they represent the same discount.
        // We should not add them. We will prioritize discount_amount as it's the final value.
        $discountAmount = (float) request('discount_amount', 0);
        $discountPercentage = (float) request('discount_percentage', 0);

        // If discount_amount is provided, use it as the source of truth.
        // Otherwise, calculate it from the percentage.
        if ($discountAmount > 0) {
            $totalDiscount = $discountAmount;
        } else {
            $totalDiscount = ($originalFee * $discountPercentage) / 100;
        }

        // Add a small tolerance for floating point comparisons
        $tolerance = 0.01;

        // Check if total discount exceeds original fee
        if ($totalDiscount > $originalFee + $tolerance) {
            $fail('Tổng chiết khấu không được vượt quá học phí gốc.');
            return;
        }

        $finalFee = max(0, $originalFee - $totalDiscount);

        // Check if total discount exceeds original fee
        if ($totalDiscount > $originalFee) {
            $fail('Tổng chiết khấu không được vượt quá học phí gốc.');
            return;
        }

        // Check minimum fee if defined
        if (isset($courseItem->custom_fields['min_fee'])) {
            $minFee = (float) $courseItem->custom_fields['min_fee'];
            if ($finalFee < $minFee) {
                $fail("Học phí sau chiết khấu không được thấp hơn " . number_format($minFee, 0, ',', '.') . " VND.");
                return;
            }
        }

        // Default minimum fee check (10% of original fee)
        // Allow 100% discount as special case
        $defaultMinFee = $originalFee * 0.1;
        if ($finalFee < $defaultMinFee && $finalFee > 0) {
            $fail('Chiết khấu quá lớn. Học phí cuối cùng phải ít nhất 10% học phí gốc hoặc bằng 0 (chiết khấu 100%).');
            return;
        }
    }
}

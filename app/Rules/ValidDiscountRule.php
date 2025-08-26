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

            if ($value >= $originalFee) {
                $fail('Số tiền chiết khấu không được lớn hơn hoặc bằng học phí gốc.');
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

        // Check if both percentage and amount discounts are applied
        $discountPercentage = request('discount_percentage', 0);
        $discountAmount = request('discount_amount', 0);

        if ($discountPercentage > 0 && $discountAmount > 0) {
            $fail('Không thể áp dụng đồng thời cả chiết khấu theo phần trăm và số tiền.');
            return;
        }

        // Calculate final fee to ensure it's not negative or too low
        $finalFee = $originalFee;
        
        if ($this->discountType === 'percentage' && $value > 0) {
            $finalFee = $originalFee * (1 - $value / 100);
        } elseif ($this->discountType === 'amount' && $value > 0) {
            $finalFee = $originalFee - $value;
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

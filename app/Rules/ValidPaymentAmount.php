<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Enrollment;

class ValidPaymentAmount implements ValidationRule
{
    protected $enrollment;
    protected $message;

    public function __construct(Enrollment $enrollment)
    {
        $this->enrollment = $enrollment;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check if amount is positive
        if ($value <= 0) {
            $fail('Số tiền thanh toán phải lớn hơn 0.');
            return;
        }

        // Check minimum amount (1,000 VND)
        if ($value < 1000) {
            $fail('Số tiền thanh toán tối thiểu là 1,000 VND.');
            return;
        }

        // Check maximum amount (100,000,000 VND)
        if ($value > 100000000) {
            $fail('Số tiền thanh toán không được vượt quá 100,000,000 VND.');
            return;
        }

        // Check remaining amount
        $remaining = $this->enrollment->getRemainingAmount();
        
        if ($remaining <= 0) {
            $fail('Học viên đã thanh toán đủ học phí.');
            return;
        }

        if ($value > $remaining) {
            $fail("Số tiền thanh toán không được vượt quá số tiền còn thiếu: " . number_format($remaining, 0, ',', '.') . " VND.");
            return;
        }

        // Check for reasonable payment amount (not too small compared to remaining)
        if ($remaining > 10000 && $value < 1000) {
            $fail('Số tiền thanh toán quá nhỏ so với số tiền còn thiếu.');
            return;
        }
    }
}

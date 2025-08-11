<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Carbon\Carbon;

class DateDDMMYYYY implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return; // Allow empty values
        }
        
        // Check if it matches dd/mm/yyyy format
        if (!preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $value)) {
            $fail('Trường :attribute phải có định dạng dd/mm/yyyy.');
            return;
        }
        
        // Try to parse the date
        $parts = explode('/', $value);
        $day = (int)$parts[0];
        $month = (int)$parts[1];  
        $year = (int)$parts[2];
        
        // Validate date parts
        if (!checkdate($month, $day, $year)) {
            $fail('Trường :attribute không phải là một ngày hợp lệ.');
            return;
        }
        
        // Optional: Check if date is not too far in the future
        // For enrollment dates, we allow some future dates but not too far
        try {
            $date = Carbon::createFromFormat('d/m/Y', $value);
            // Only restrict if date is more than 1 year in the future
            if ($date > Carbon::now()->addYear()) {
                $fail('Trường :attribute không được quá xa trong tương lai.');
                return;
            }
        } catch (\Exception $e) {
            // If Carbon fails to parse, we already caught it with checkdate above
            $fail('Trường :attribute không phải là một ngày hợp lệ.');
        }
    }
}

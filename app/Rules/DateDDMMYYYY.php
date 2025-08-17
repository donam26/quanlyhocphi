<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Carbon\Carbon;

class DateDDMMYYYY implements ValidationRule
{
    private $allowPastDates;
    private $maxFutureDays;

    /**
     * Create a new rule instance.
     *
     * @param bool $allowPastDates
     * @param int $maxFutureDays
     */
    public function __construct($allowPastDates = true, $maxFutureDays = 365)
    {
        $this->allowPastDates = $allowPastDates;
        $this->maxFutureDays = $maxFutureDays;
    }

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

        // Additional business logic validation
        try {
            $date = Carbon::createFromFormat('d/m/Y', $value);
            $now = Carbon::now();

            // Check past dates if not allowed
            if (!$this->allowPastDates && $date < $now->startOfDay()) {
                $fail('Trường :attribute không được trong quá khứ.');
                return;
            }

            // Check future dates limit
            if ($date > $now->addDays($this->maxFutureDays)) {
                $fail("Trường :attribute không được quá {$this->maxFutureDays} ngày trong tương lai.");
                return;
            }
        } catch (\Exception $e) {
            // If Carbon fails to parse, we already caught it with checkdate above
            $fail('Trường :attribute không phải là một ngày hợp lệ.');
        }
    }
}

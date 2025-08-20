<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class WhitelistedDomain implements ValidationRule
{
    protected $allowedDomains;

    public function __construct()
    {
        // Get allowed domains from config
        $this->allowedDomains = config('app.allowed_redirect_domains', [
            'localhost',
            '127.0.0.1',
            config('app.url'),
            'neube.winhouse.id.vn'
        ]);
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return; // Allow empty values
        }

        $parsedUrl = parse_url($value);
        
        if (!$parsedUrl || !isset($parsedUrl['host'])) {
            $fail('URL không hợp lệ.');
            return;
        }

        $host = $parsedUrl['host'];
        
        // Check if domain is in whitelist
        $isAllowed = false;
        foreach ($this->allowedDomains as $allowedDomain) {
            // Remove protocol if present
            $allowedDomain = preg_replace('/^https?:\/\//', '', $allowedDomain);
            
            if ($host === $allowedDomain || str_ends_with($host, '.' . $allowedDomain)) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed) {
            $fail('Domain không được phép. Chỉ cho phép: ' . implode(', ', $this->allowedDomains));
        }
    }
}

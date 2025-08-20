<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'sepay' => [
        'bank_code' => env('SEPAY_BANK_CODE', 'MB'),
        'bank_number' => env('SEPAY_BANK_NUMBER'),
        'bank_name' => env('SEPAY_BANK_NAME', 'MB Bank'),
        'account_owner' => env('SEPAY_ACCOUNT_OWNER'),
        'webhook_secret' => env('SEPAY_WEBHOOK_SECRET'),
        'api_key' => env('SEPAY_API_KEY'),
        'pattern' => env('SEPAY_PATTERN', 'HOCPHI'),
    ],

];

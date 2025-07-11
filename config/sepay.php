<?php

return [
    'api_token' => env('SEPAY_API_TOKEN', 'NEP0VPMQNMBMGQCQ9KAO1HEREAXKDKVLT3SI82TI4NHDSLS4PJFLFYWD8BPAH763'),
    'pattern' => env('SEPAY_MATCH_PATTERN', 'SE'),
    'bank_number' => env('SEPAY_BANK_NUMBER', '103870429701'),
    'bank_name' => env('SEPAY_BANK_NAME', 'Vietinbank'),
    'bank_code' => env('SEPAY_BANK_CODE', 'ICB'),
    'account_owner' => env('SEPAY_ACCOUNT_OWNER', 'DO HOANG NAM'),
    'api_url' => env('SEPAY_API_URL', 'https://api.sepay.vn/api/v2'),
    'qr_url' => env('QR_CODE_URL', 'https://api.sepay.vn/api/v2/payment/qr'),
    'vietqr_url' => env('VIETQR_URL', 'https://api.vietqr.io/v2/generate'),
];

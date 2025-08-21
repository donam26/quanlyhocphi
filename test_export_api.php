<?php

// Test script để kiểm tra API export endpoint
echo "=== TESTING EXPORT API ENDPOINTS ===\n\n";

// Configuration
$baseUrl = 'http://localhost:8000/api'; // Adjust this to your Laravel app URL
$token = 'your-auth-token-here'; // You'll need to get this from login

// Test data
$testCases = [
    [
        'name' => 'Basic Student Export',
        'endpoint' => '/students/export',
        'data' => [
            'columns' => ['full_name', 'phone', 'email', 'province'],
            'filters' => []
        ]
    ],
    [
        'name' => 'Student Export with Filters',
        'endpoint' => '/students/export',
        'data' => [
            'columns' => ['full_name', 'phone', 'email', 'gender', 'province'],
            'filters' => [
                'gender' => 'male',
                'search' => 'Nguyễn'
            ]
        ]
    ],
    [
        'name' => 'Payment Export',
        'endpoint' => '/payments/export',
        'data' => [
            'columns' => ['student_name', 'student_phone', 'amount', 'payment_date', 'status'],
            'filters' => [
                'status' => 'confirmed'
            ]
        ]
    ],
    [
        'name' => 'Enrollment Export',
        'endpoint' => '/enrollments/export',
        'data' => [
            'columns' => ['student_name', 'course_name', 'enrollment_date', 'status'],
            'filters' => [
                'status' => 'active'
            ]
        ]
    ]
];

function makeApiCall($url, $data, $token) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Authorization: Bearer ' . $token,
            'X-Requested-With: XMLHttpRequest'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    return [
        'response' => $response,
        'http_code' => $httpCode,
        'content_type' => $contentType,
        'error' => $error
    ];
}

// Run tests
foreach ($testCases as $index => $testCase) {
    echo ($index + 1) . ". Testing: " . $testCase['name'] . "\n";
    echo "Endpoint: " . $testCase['endpoint'] . "\n";
    echo "Data: " . json_encode($testCase['data'], JSON_PRETTY_PRINT) . "\n";
    
    $url = $baseUrl . $testCase['endpoint'];
    $result = makeApiCall($url, $testCase['data'], $token);
    
    echo "HTTP Code: " . $result['http_code'] . "\n";
    echo "Content Type: " . $result['content_type'] . "\n";
    
    if ($result['error']) {
        echo "❌ CURL Error: " . $result['error'] . "\n";
    } elseif ($result['http_code'] === 200) {
        // Check if response is Excel file
        if (strpos($result['content_type'], 'spreadsheet') !== false || 
            strpos($result['content_type'], 'excel') !== false) {
            echo "✅ SUCCESS: Excel file received\n";
            echo "File size: " . strlen($result['response']) . " bytes\n";
            
            // Save file for verification
            $fileName = 'test_' . strtolower(str_replace(' ', '_', $testCase['name'])) . '_' . date('Y_m_d_H_i_s') . '.xlsx';
            file_put_contents($fileName, $result['response']);
            echo "File saved as: " . $fileName . "\n";
        } else {
            echo "⚠️ WARNING: Response is not an Excel file\n";
            echo "Response: " . substr($result['response'], 0, 500) . "...\n";
        }
    } elseif ($result['http_code'] === 401) {
        echo "❌ AUTHENTICATION ERROR: Invalid or missing token\n";
        echo "Please update the \$token variable with a valid authentication token\n";
    } elseif ($result['http_code'] === 422) {
        echo "❌ VALIDATION ERROR:\n";
        $errorResponse = json_decode($result['response'], true);
        if ($errorResponse) {
            echo json_encode($errorResponse, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo $result['response'] . "\n";
        }
    } else {
        echo "❌ ERROR: HTTP " . $result['http_code'] . "\n";
        echo "Response: " . $result['response'] . "\n";
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
}

echo "=== API TESTING COMPLETED ===\n\n";

// Instructions for getting auth token
echo "📝 INSTRUCTIONS FOR GETTING AUTH TOKEN:\n";
echo "1. Make a POST request to {$baseUrl}/login with valid credentials\n";
echo "2. Extract the 'token' from the response\n";
echo "3. Update the \$token variable in this script\n";
echo "4. Run this script again\n\n";

echo "Example login request:\n";
echo "curl -X POST {$baseUrl}/login \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -d '{\"email\":\"admin@example.com\",\"password\":\"password\"}'\n\n";

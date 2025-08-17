<?php

/**
 * Test script để kiểm tra các API thanh toán mới
 * Chạy: php test_payment_apis.php
 */

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "🧪 Testing Payment APIs...\n\n";

// Test 1: Get Unpaid Enrollments
echo "1️⃣ Testing GET /api/payments/unpaid-enrollments\n";
try {
    $request = Request::create('/api/payments/unpaid-enrollments', 'GET');
    $response = $kernel->handle($request);
    
    echo "Status: " . $response->getStatusCode() . "\n";
    $data = json_decode($response->getContent(), true);
    
    if ($data['success'] ?? false) {
        echo "✅ Success: Found " . count($data['data']['data'] ?? []) . " unpaid enrollments\n";
    } else {
        echo "❌ Failed: " . ($data['message'] ?? 'Unknown error') . "\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Get Payment Overview
echo "2️⃣ Testing GET /api/payments/payment-overview\n";
try {
    $request = Request::create('/api/payments/payment-overview', 'GET');
    $response = $kernel->handle($request);
    
    echo "Status: " . $response->getStatusCode() . "\n";
    $data = json_decode($response->getContent(), true);
    
    if ($data['success'] ?? false) {
        echo "✅ Success: Overview data retrieved\n";
        echo "   - Total enrollments: " . ($data['data']['total_active_enrollments'] ?? 0) . "\n";
        echo "   - Unpaid count: " . ($data['data']['unpaid_count'] ?? 0) . "\n";
        echo "   - Payment percentage: " . ($data['data']['payment_percentage'] ?? 0) . "%\n";
    } else {
        echo "❌ Failed: " . ($data['message'] ?? 'Unknown error') . "\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Test SePay Payment Initiation (with mock data)
echo "3️⃣ Testing POST /api/payments/sepay/initiate\n";
try {
    // Tìm một enrollment để test
    $enrollment = \App\Models\Enrollment::with(['student', 'courseItem'])
        ->where('status', \App\Enums\EnrollmentStatus::ACTIVE)
        ->first();
    
    if ($enrollment) {
        $remaining = $enrollment->final_fee - $enrollment->getTotalPaidAmount();
        
        if ($remaining > 0) {
            $request = Request::create('/api/payments/sepay/initiate', 'POST', [
                'enrollment_id' => $enrollment->id,
                'amount' => min($remaining, 100000) // Test với 100k hoặc số tiền còn thiếu
            ]);
            
            $response = $kernel->handle($request);
            echo "Status: " . $response->getStatusCode() . "\n";
            
            $data = json_decode($response->getContent(), true);
            
            if ($data['success'] ?? false) {
                echo "✅ Success: SePay payment initiated\n";
                echo "   - Payment ID: " . ($data['data']['payment_id'] ?? 'N/A') . "\n";
                echo "   - QR generated: " . (isset($data['data']['qr_data']) ? 'Yes' : 'No') . "\n";
            } else {
                echo "❌ Failed: " . ($data['message'] ?? 'Unknown error') . "\n";
            }
        } else {
            echo "⚠️ Skipped: No unpaid enrollments found for testing\n";
        }
    } else {
        echo "⚠️ Skipped: No active enrollments found\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Test Send Reminder
echo "4️⃣ Testing POST /api/payments/send-reminder\n";
try {
    // Tìm một enrollment chưa thanh toán để test
    $unpaidEnrollments = \App\Models\Enrollment::with(['student', 'courseItem'])
        ->where('status', \App\Enums\EnrollmentStatus::ACTIVE)
        ->whereRaw('(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payments.enrollment_id = enrollments.id AND payments.status = "confirmed") < enrollments.final_fee')
        ->limit(1)
        ->get();
    
    if ($unpaidEnrollments->count() > 0) {
        $request = Request::create('/api/payments/send-reminder', 'POST', [
            'enrollment_ids' => [$unpaidEnrollments->first()->id],
            'message' => 'Test reminder message'
        ]);
        
        $response = $kernel->handle($request);
        echo "Status: " . $response->getStatusCode() . "\n";
        
        $data = json_decode($response->getContent(), true);
        
        if ($data['success'] ?? false) {
            echo "✅ Success: Reminder sent\n";
            echo "   - Success count: " . ($data['data']['success_count'] ?? 0) . "\n";
            echo "   - Failed count: " . ($data['data']['failed_count'] ?? 0) . "\n";
        } else {
            echo "❌ Failed: " . ($data['message'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "⚠️ Skipped: No unpaid enrollments found for testing\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 5: Check Database Structure
echo "5️⃣ Testing Database Structure\n";
try {
    $enrollmentCount = \App\Models\Enrollment::count();
    $paymentCount = \App\Models\Payment::count();
    $studentCount = \App\Models\Student::count();
    $courseCount = \App\Models\CourseItem::count();
    
    echo "✅ Database structure OK:\n";
    echo "   - Enrollments: $enrollmentCount\n";
    echo "   - Payments: $paymentCount\n";
    echo "   - Students: $studentCount\n";
    echo "   - Courses: $courseCount\n";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n🎉 Testing completed!\n";

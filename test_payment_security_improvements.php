<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\SePayWebhookController;
use App\Models\Payment;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\CourseItem;
use App\Models\AuditLog;
use App\Services\PaymentMetricsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔒 TESTING PAYMENT SECURITY IMPROVEMENTS\n";
echo "=" . str_repeat("=", 50) . "\n\n";

// Test 1: Webhook Signature Verification
echo "1. Testing Webhook Signature Verification...\n";
try {
    $webhookController = new SePayWebhookController();
    
    // Create a test request without signature
    $request = Request::create('/api/sepay/webhook', 'POST', [
        'id' => 'test_webhook_123',
        'transaction_content' => 'HOCPHI 123',
        'amount_in' => 1000000
    ]);
    
    // This should fail due to missing signature
    $response = $webhookController->handleWebhook($request);
    
    if ($response->getStatusCode() === 401) {
        echo "✅ Webhook signature verification working correctly\n";
    } else {
        echo "❌ Webhook signature verification failed\n";
    }
} catch (Exception $e) {
    echo "⚠️  Webhook test error: " . $e->getMessage() . "\n";
}

// Test 2: Rate Limiting (simulated)
echo "\n2. Testing Rate Limiting Configuration...\n";
try {
    // Check if rate limiting middleware is configured
    $routes = app('router')->getRoutes();
    $sePayRoute = null;
    
    foreach ($routes as $route) {
        if (str_contains($route->uri(), 'sepay/initiate')) {
            $sePayRoute = $route;
            break;
        }
    }
    
    if ($sePayRoute && in_array('throttle:10,1', $sePayRoute->middleware())) {
        echo "✅ Rate limiting configured correctly for SePay initiate\n";
    } else {
        echo "❌ Rate limiting not found or incorrectly configured\n";
    }
} catch (Exception $e) {
    echo "⚠️  Rate limiting test error: " . $e->getMessage() . "\n";
}

// Test 3: Database Constraints
echo "\n3. Testing Database Constraints...\n";
try {
    // Test positive amount constraint
    try {
        DB::statement("INSERT INTO payments (enrollment_id, amount, payment_method, payment_date, status) VALUES (1, -1000, 'cash', NOW(), 'pending')");
        echo "❌ Negative amount constraint not working\n";
    } catch (Exception $e) {
        echo "✅ Positive amount constraint working\n";
    }
    
    // Test valid status constraint
    try {
        DB::statement("INSERT INTO payments (enrollment_id, amount, payment_method, payment_date, status) VALUES (1, 1000, 'cash', NOW(), 'invalid_status')");
        echo "❌ Valid status constraint not working\n";
    } catch (Exception $e) {
        echo "✅ Valid status constraint working\n";
    }
    
} catch (Exception $e) {
    echo "⚠️  Database constraint test error: " . $e->getMessage() . "\n";
}

// Test 4: Audit Logging
echo "\n4. Testing Audit Logging...\n";
try {
    // Create a test payment to trigger audit logging
    $enrollment = Enrollment::first();
    if ($enrollment) {
        $payment = new Payment([
            'enrollment_id' => $enrollment->id,
            'amount' => 100000,
            'payment_method' => 'cash',
            'payment_date' => now(),
            'status' => 'pending'
        ]);
        
        $payment->save();
        
        // Check if audit log was created
        $auditLog = AuditLog::where('model_type', 'App\\Models\\Payment')
            ->where('model_id', $payment->id)
            ->where('action', 'created')
            ->first();
            
        if ($auditLog) {
            echo "✅ Audit logging working correctly\n";
            echo "   - User ID: " . ($auditLog->user_id ?? 'System') . "\n";
            echo "   - Action: " . $auditLog->action . "\n";
            echo "   - Timestamp: " . $auditLog->created_at . "\n";
        } else {
            echo "❌ Audit logging not working\n";
        }
        
        // Clean up
        $payment->delete();
    } else {
        echo "⚠️  No enrollment found for audit test\n";
    }
} catch (Exception $e) {
    echo "⚠️  Audit logging test error: " . $e->getMessage() . "\n";
}

// Test 5: Payment Metrics
echo "\n5. Testing Payment Metrics...\n";
try {
    // Test metrics recording
    $enrollment = Enrollment::first();
    if ($enrollment) {
        $payment = new Payment([
            'enrollment_id' => $enrollment->id,
            'amount' => 500000,
            'payment_method' => 'sepay',
            'payment_date' => now(),
            'status' => 'pending'
        ]);
        
        $payment->save();
        
        // Check if metrics were recorded
        $metricsKey = 'metrics.payments.created.total.' . now()->format('Y-m-d');
        $count = Cache::get($metricsKey, 0);
        
        if ($count > 0) {
            echo "✅ Payment metrics recording working\n";
            echo "   - Today's payment count: " . $count . "\n";
        } else {
            echo "⚠️  Payment metrics may not be recording (cache empty)\n";
        }
        
        // Test dashboard metrics
        $dashboardMetrics = PaymentMetricsService::getDashboardMetrics();
        if (isset($dashboardMetrics['today'])) {
            echo "✅ Dashboard metrics service working\n";
            echo "   - Today's total payments: " . $dashboardMetrics['today']['total_payments'] . "\n";
        } else {
            echo "❌ Dashboard metrics service not working\n";
        }
        
        // Clean up
        $payment->delete();
    }
} catch (Exception $e) {
    echo "⚠️  Payment metrics test error: " . $e->getMessage() . "\n";
}

// Test 6: Validation Rules
echo "\n6. Testing Custom Validation Rules...\n";
try {
    $enrollment = Enrollment::first();
    if ($enrollment) {
        // Test ValidPaymentAmount rule
        $rule = new \App\Rules\ValidPaymentAmount($enrollment);
        
        // Test with negative amount
        $isValid = true;
        $rule->validate('amount', -1000, function($message) use (&$isValid) {
            $isValid = false;
        });
        
        if (!$isValid) {
            echo "✅ ValidPaymentAmount rule working for negative amounts\n";
        } else {
            echo "❌ ValidPaymentAmount rule not catching negative amounts\n";
        }
        
        // Test WhitelistedDomain rule
        $domainRule = new \App\Rules\WhitelistedDomain();
        
        $isValidDomain = true;
        $domainRule->validate('redirect_url', 'https://malicious-site.com', function($message) use (&$isValidDomain) {
            $isValidDomain = false;
        });
        
        if (!$isValidDomain) {
            echo "✅ WhitelistedDomain rule working correctly\n";
        } else {
            echo "❌ WhitelistedDomain rule not blocking malicious domains\n";
        }
    }
} catch (Exception $e) {
    echo "⚠️  Validation rules test error: " . $e->getMessage() . "\n";
}

// Test 7: Idempotency
echo "\n7. Testing Idempotency Features...\n";
try {
    // Test webhook idempotency
    $webhookId = 'test_webhook_' . time();
    
    // First call should be processed
    $controller = new SePayWebhookController();
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('isWebhookProcessed');
    $method->setAccessible(true);
    
    $firstCall = $method->invoke($controller, $webhookId);
    $secondCall = $method->invoke($controller, $webhookId);
    
    if (!$firstCall && $secondCall) {
        echo "✅ Webhook idempotency working correctly\n";
    } else {
        echo "❌ Webhook idempotency not working\n";
    }
} catch (Exception $e) {
    echo "⚠️  Idempotency test error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "🎉 PAYMENT SECURITY IMPROVEMENTS TEST COMPLETED\n";
echo "Check the results above to ensure all security features are working correctly.\n";
echo str_repeat("=", 60) . "\n";

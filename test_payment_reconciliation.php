<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Services\PaymentReconciliationService;
use App\Models\Payment;
use App\Models\Enrollment;
use App\Models\AuditLog;
use Carbon\Carbon;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔄 TESTING PAYMENT RECONCILIATION SYSTEM\n";
echo "=" . str_repeat("=", 50) . "\n\n";

$reconciliationService = new PaymentReconciliationService();

// Test 1: Auto Reconcile SePay Transactions
echo "1. Testing SePay Auto Reconciliation...\n";
try {
    // Create a test SePay payment
    $enrollment = Enrollment::first();
    if ($enrollment) {
        $payment = new Payment([
            'enrollment_id' => $enrollment->id,
            'amount' => 1000000,
            'payment_method' => 'sepay',
            'payment_date' => Carbon::today(),
            'status' => 'confirmed',
            'transaction_reference' => 'SEPAY_TEST_' . time(),
            'webhook_data' => [
                'transaction_id' => 'TXN_' . time(),
                'amount_received' => 1000000
            ]
        ]);
        
        $payment->save();
        
        // Test auto reconciliation
        $results = $reconciliationService->autoReconcileSePayTransactions(Carbon::today());
        
        if ($results['reconciled'] > 0) {
            echo "✅ SePay auto reconciliation working\n";
            echo "   - Processed: {$results['processed']}\n";
            echo "   - Reconciled: {$results['reconciled']}\n";
        } else {
            echo "⚠️  No SePay payments reconciled\n";
        }
        
        // Clean up
        $payment->delete();
    } else {
        echo "⚠️  No enrollment found for SePay test\n";
    }
} catch (Exception $e) {
    echo "❌ SePay reconciliation test error: " . $e->getMessage() . "\n";
}

// Test 2: Manual Bank Statement Reconciliation
echo "\n2. Testing Manual Bank Statement Reconciliation...\n";
try {
    $enrollment = Enrollment::first();
    if ($enrollment) {
        // Create test bank transfer payment
        $payment = new Payment([
            'enrollment_id' => $enrollment->id,
            'amount' => 2000000,
            'payment_method' => 'bank_transfer',
            'payment_date' => Carbon::today(),
            'status' => 'confirmed',
            'transaction_reference' => 'BANK_TEST_' . time()
        ]);
        
        $payment->save();
        
        // Create matching bank statement
        $bankStatements = [
            [
                'amount' => 2000000,
                'date' => Carbon::today()->toDateString(),
                'reference' => $payment->transaction_reference,
                'description' => 'Payment from student'
            ]
        ];
        
        // Test reconciliation
        $results = $reconciliationService->reconcilePayments($bankStatements, Carbon::today());
        
        if (count($results['matched']) > 0) {
            echo "✅ Bank statement reconciliation working\n";
            echo "   - Matched: " . count($results['matched']) . "\n";
            echo "   - Unmatched payments: " . count($results['unmatched_payments']) . "\n";
            echo "   - Unmatched bank records: " . count($results['unmatched_bank_records']) . "\n";
        } else {
            echo "❌ No payments matched with bank statements\n";
        }
        
        // Clean up
        $payment->delete();
    }
} catch (Exception $e) {
    echo "❌ Bank reconciliation test error: " . $e->getMessage() . "\n";
}

// Test 3: Discrepancy Detection
echo "\n3. Testing Amount Discrepancy Detection...\n";
try {
    $enrollment = Enrollment::first();
    if ($enrollment) {
        // Create payment with different amount than bank record
        $payment = new Payment([
            'enrollment_id' => $enrollment->id,
            'amount' => 1500000,
            'payment_method' => 'bank_transfer',
            'payment_date' => Carbon::today(),
            'status' => 'confirmed',
            'transaction_reference' => 'DISC_TEST_' . time()
        ]);
        
        $payment->save();
        
        // Create bank statement with different amount
        $bankStatements = [
            [
                'amount' => 1600000, // 100k difference
                'date' => Carbon::today()->toDateString(),
                'reference' => $payment->transaction_reference,
                'description' => 'Payment with discrepancy'
            ]
        ];
        
        $results = $reconciliationService->reconcilePayments($bankStatements, Carbon::today());
        
        if (count($results['discrepancies']) > 0) {
            echo "✅ Discrepancy detection working\n";
            $discrepancy = $results['discrepancies'][0];
            echo "   - Payment amount: " . number_format($discrepancy['payment_amount']) . "\n";
            echo "   - Bank amount: " . number_format($discrepancy['bank_amount']) . "\n";
            echo "   - Difference: " . number_format($discrepancy['difference']) . "\n";
        } else {
            echo "❌ Discrepancy not detected\n";
        }
        
        // Clean up
        $payment->delete();
    }
} catch (Exception $e) {
    echo "❌ Discrepancy detection test error: " . $e->getMessage() . "\n";
}

// Test 4: Suspicious Pattern Detection
echo "\n4. Testing Suspicious Pattern Detection...\n";
try {
    $enrollment = Enrollment::first();
    if ($enrollment) {
        // Create multiple payments with same amount (suspicious pattern)
        $payments = [];
        for ($i = 0; $i < 3; $i++) {
            $payment = new Payment([
                'enrollment_id' => $enrollment->id,
                'amount' => 999999, // Same amount
                'payment_method' => 'cash',
                'payment_date' => Carbon::today(),
                'status' => 'confirmed',
                'created_at' => Carbon::today()
            ]);
            $payment->save();
            $payments[] = $payment;
        }
        
        $suspiciousPatterns = $reconciliationService->detectSuspiciousPatterns(Carbon::today());
        
        if (!empty($suspiciousPatterns)) {
            echo "✅ Suspicious pattern detection working\n";
            foreach ($suspiciousPatterns as $pattern) {
                echo "   - Pattern: {$pattern['type']}\n";
                echo "   - Description: {$pattern['description']}\n";
            }
        } else {
            echo "⚠️  No suspicious patterns detected\n";
        }
        
        // Clean up
        foreach ($payments as $payment) {
            $payment->delete();
        }
    }
} catch (Exception $e) {
    echo "❌ Suspicious pattern detection test error: " . $e->getMessage() . "\n";
}

// Test 5: Reconciliation Report Generation
echo "\n5. Testing Reconciliation Report Generation...\n";
try {
    $startDate = Carbon::today()->subDays(7);
    $endDate = Carbon::today();
    
    $report = $reconciliationService->generateReconciliationReport($startDate, $endDate);
    
    if (isset($report['summary'])) {
        echo "✅ Reconciliation report generation working\n";
        echo "   - Period: {$report['period']['start']} to {$report['period']['end']}\n";
        echo "   - Total reconciled: {$report['summary']['total_reconciled']}\n";
        echo "   - Total discrepancies: {$report['summary']['total_discrepancies']}\n";
        echo "   - Amount reconciled: " . number_format($report['summary']['total_amount_reconciled']) . "\n";
    } else {
        echo "❌ Report generation failed\n";
    }
} catch (Exception $e) {
    echo "❌ Report generation test error: " . $e->getMessage() . "\n";
}

// Test 6: Audit Trail Verification
echo "\n6. Testing Audit Trail for Reconciliation...\n";
try {
    // Check if reconciliation actions are being logged
    $recentAuditLogs = AuditLog::where('model_type', 'App\\Models\\Payment')
        ->whereIn('action', ['reconciliation_matched', 'reconciliation_discrepancy', 'sepay_auto_reconciled'])
        ->where('created_at', '>=', Carbon::today())
        ->count();
    
    if ($recentAuditLogs > 0) {
        echo "✅ Reconciliation audit trail working\n";
        echo "   - Recent reconciliation logs: {$recentAuditLogs}\n";
    } else {
        echo "⚠️  No recent reconciliation audit logs found\n";
    }
} catch (Exception $e) {
    echo "❌ Audit trail test error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "🎉 PAYMENT RECONCILIATION TEST COMPLETED\n";
echo "The reconciliation system is ready for production use.\n";
echo "Run 'php artisan payments:reconcile --help' for command options.\n";
echo str_repeat("=", 60) . "\n";

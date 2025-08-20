<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PaymentMetricsService
{
    /**
     * Record payment creation metric
     */
    public static function recordPaymentCreated(Payment $payment)
    {
        try {
            $metrics = [
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'method' => $payment->payment_method,
                'amount_range' => self::getAmountRange($payment->amount),
                'timestamp' => now()->toISOString()
            ];

            // Store in cache for real-time dashboard
            self::incrementCounter('payments.created.total');
            self::incrementCounter("payments.created.method.{$payment->payment_method}");
            self::incrementCounter("payments.created.range.{$metrics['amount_range']}");

            // Log for analytics
            Log::channel('metrics')->info('payment.created', $metrics);

        } catch (\Exception $e) {
            Log::error('Error recording payment creation metric', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Record payment confirmation metric
     */
    public static function recordPaymentConfirmed(Payment $payment, string $source = 'manual')
    {
        try {
            $metrics = [
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'method' => $payment->payment_method,
                'source' => $source, // manual, webhook, etc.
                'processing_time' => $payment->confirmed_at ? 
                    $payment->created_at->diffInSeconds($payment->confirmed_at) : null,
                'timestamp' => now()->toISOString()
            ];

            // Store in cache
            self::incrementCounter('payments.confirmed.total');
            self::incrementCounter("payments.confirmed.method.{$payment->payment_method}");
            self::incrementCounter("payments.confirmed.source.{$source}");

            // Update success rate
            self::updateSuccessRate($payment->payment_method);

            // Log for analytics
            Log::channel('metrics')->info('payment.confirmed', $metrics);

        } catch (\Exception $e) {
            Log::error('Error recording payment confirmation metric', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Record payment failure metric
     */
    public static function recordPaymentFailed(array $paymentData, string $reason, string $source = 'api')
    {
        try {
            $metrics = [
                'amount' => $paymentData['amount'] ?? null,
                'method' => $paymentData['payment_method'] ?? 'unknown',
                'reason' => $reason,
                'source' => $source,
                'timestamp' => now()->toISOString()
            ];

            // Store in cache
            self::incrementCounter('payments.failed.total');
            self::incrementCounter("payments.failed.method.{$metrics['method']}");
            self::incrementCounter("payments.failed.reason.{$reason}");

            // Log for analytics
            Log::channel('metrics')->info('payment.failed', $metrics);

        } catch (\Exception $e) {
            Log::error('Error recording payment failure metric', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get payment metrics for dashboard
     */
    public static function getDashboardMetrics()
    {
        $cacheKey = 'payment_metrics_dashboard';
        
        return Cache::remember($cacheKey, 300, function () { // 5 minutes cache
            $today = Carbon::today();
            $thisMonth = Carbon::now()->startOfMonth();
            
            return [
                'today' => [
                    'total_payments' => Payment::whereDate('created_at', $today)->count(),
                    'confirmed_payments' => Payment::whereDate('confirmed_at', $today)->count(),
                    'total_amount' => Payment::whereDate('payment_date', $today)
                        ->where('status', 'confirmed')->sum('amount'),
                    'by_method' => Payment::whereDate('created_at', $today)
                        ->groupBy('payment_method')
                        ->selectRaw('payment_method, count(*) as count, sum(amount) as total_amount')
                        ->get()
                ],
                'this_month' => [
                    'total_payments' => Payment::whereDate('created_at', '>=', $thisMonth)->count(),
                    'confirmed_payments' => Payment::whereDate('confirmed_at', '>=', $thisMonth)->count(),
                    'total_amount' => Payment::whereDate('payment_date', '>=', $thisMonth)
                        ->where('status', 'confirmed')->sum('amount'),
                ],
                'success_rates' => self::getSuccessRates(),
                'recent_failures' => self::getRecentFailures()
            ];
        });
    }

    /**
     * Get amount range for categorization
     */
    private static function getAmountRange($amount)
    {
        if ($amount < 100000) return 'under_100k';
        if ($amount < 500000) return '100k_500k';
        if ($amount < 1000000) return '500k_1m';
        if ($amount < 5000000) return '1m_5m';
        return 'over_5m';
    }

    /**
     * Increment counter in cache
     */
    private static function incrementCounter(string $key)
    {
        $cacheKey = "metrics.{$key}." . Carbon::today()->format('Y-m-d');
        Cache::increment($cacheKey, 1);
        
        // Set expiration to 7 days
        Cache::put($cacheKey, Cache::get($cacheKey, 0), 7 * 24 * 60 * 60);
    }

    /**
     * Update success rate for payment method
     */
    private static function updateSuccessRate(string $method)
    {
        $cacheKey = "payment_success_rate_{$method}";
        
        // Calculate success rate for last 24 hours
        $total = Payment::where('payment_method', $method)
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->count();
            
        $confirmed = Payment::where('payment_method', $method)
            ->where('confirmed_at', '>=', Carbon::now()->subDay())
            ->count();
            
        $rate = $total > 0 ? ($confirmed / $total) * 100 : 0;
        
        Cache::put($cacheKey, $rate, 3600); // 1 hour cache
    }

    /**
     * Get success rates for all payment methods
     */
    private static function getSuccessRates()
    {
        $methods = ['cash', 'bank_transfer', 'sepay', 'card', 'qr_code'];
        $rates = [];
        
        foreach ($methods as $method) {
            $rates[$method] = Cache::get("payment_success_rate_{$method}", 0);
        }
        
        return $rates;
    }

    /**
     * Get recent payment failures for monitoring
     */
    private static function getRecentFailures()
    {
        return AuditLog::where('model_type', 'App\\Models\\Payment')
            ->where('action', 'failed')
            ->where('created_at', '>=', Carbon::now()->subHours(24))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['action', 'metadata', 'created_at']);
    }

    /**
     * Alert if payment failure rate is too high
     */
    public static function checkFailureRateAlert(string $method)
    {
        $failureRate = 100 - Cache::get("payment_success_rate_{$method}", 100);
        
        if ($failureRate > 20) { // Alert if failure rate > 20%
            Log::alert("High payment failure rate detected", [
                'method' => $method,
                'failure_rate' => $failureRate,
                'timestamp' => now()->toISOString()
            ]);
            
            // Could send notification to admin here
        }
    }
}

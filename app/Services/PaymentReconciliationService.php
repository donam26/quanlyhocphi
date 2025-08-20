<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PaymentReconciliationService
{
    /**
     * Reconcile payments with bank statements
     */
    public function reconcilePayments(array $bankStatements, Carbon $date = null)
    {
        $date = $date ?? Carbon::today();
        $results = [
            'matched' => [],
            'unmatched_payments' => [],
            'unmatched_bank_records' => [],
            'discrepancies' => []
        ];

        try {
            DB::beginTransaction();

            // Get payments for the date
            $payments = Payment::whereDate('payment_date', $date)
                ->where('status', 'confirmed')
                ->whereIn('payment_method', ['bank_transfer', 'sepay'])
                ->get();

            // Process bank statements
            foreach ($bankStatements as $bankRecord) {
                $matchedPayment = $this->findMatchingPayment($payments, $bankRecord);
                
                if ($matchedPayment) {
                    $this->processMatch($matchedPayment, $bankRecord, $results);
                } else {
                    $results['unmatched_bank_records'][] = $bankRecord;
                }
            }

            // Find unmatched payments
            $matchedPaymentIds = collect($results['matched'])->pluck('payment_id');
            $results['unmatched_payments'] = $payments->whereNotIn('id', $matchedPaymentIds)->values();

            DB::commit();

            // Log reconciliation results
            Log::channel('payments')->info('Payment reconciliation completed', [
                'date' => $date->toDateString(),
                'matched_count' => count($results['matched']),
                'unmatched_payments' => count($results['unmatched_payments']),
                'unmatched_bank_records' => count($results['unmatched_bank_records']),
                'discrepancies' => count($results['discrepancies'])
            ]);

            return $results;

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Payment reconciliation failed', [
                'date' => $date->toDateString(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Find matching payment for bank record
     */
    private function findMatchingPayment($payments, array $bankRecord)
    {
        // Try to match by transaction reference
        if (isset($bankRecord['reference'])) {
            $payment = $payments->where('transaction_reference', $bankRecord['reference'])->first();
            if ($payment) return $payment;
        }

        // Try to match by amount and approximate time
        $amount = $bankRecord['amount'] ?? 0;
        $bankDate = Carbon::parse($bankRecord['date'] ?? now());

        return $payments->filter(function ($payment) use ($amount, $bankDate) {
            $amountMatch = abs($payment->amount - $amount) < 0.01;
            $dateMatch = abs($payment->payment_date->diffInHours($bankDate)) <= 24;
            
            return $amountMatch && $dateMatch;
        })->first();
    }

    /**
     * Process matched payment and bank record
     */
    private function processMatch(Payment $payment, array $bankRecord, array &$results)
    {
        $match = [
            'payment_id' => $payment->id,
            'bank_record' => $bankRecord,
            'status' => 'matched'
        ];

        // Check for amount discrepancies
        $paymentAmount = $payment->amount;
        $bankAmount = $bankRecord['amount'] ?? 0;

        if (abs($paymentAmount - $bankAmount) > 0.01) {
            $match['status'] = 'discrepancy';
            $match['discrepancy_type'] = 'amount_mismatch';
            $match['payment_amount'] = $paymentAmount;
            $match['bank_amount'] = $bankAmount;
            $match['difference'] = $bankAmount - $paymentAmount;

            $results['discrepancies'][] = $match;

            // Log discrepancy
            AuditLog::log(
                'App\\Models\\Payment',
                $payment->id,
                'reconciliation_discrepancy',
                null,
                null,
                [
                    'type' => 'amount_mismatch',
                    'payment_amount' => $paymentAmount,
                    'bank_amount' => $bankAmount,
                    'difference' => $bankAmount - $paymentAmount,
                    'bank_record' => $bankRecord
                ]
            );
        } else {
            $results['matched'][] = $match;

            // Log successful match
            AuditLog::log(
                'App\\Models\\Payment',
                $payment->id,
                'reconciliation_matched',
                null,
                null,
                [
                    'bank_record' => $bankRecord,
                    'matched_at' => now()->toISOString()
                ]
            );
        }
    }

    /**
     * Generate reconciliation report
     */
    public function generateReconciliationReport(Carbon $startDate, Carbon $endDate)
    {
        $report = [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString()
            ],
            'summary' => [],
            'details' => []
        ];

        // Get reconciliation audit logs
        $reconciliationLogs = AuditLog::where('model_type', 'App\\Models\\Payment')
            ->whereIn('action', ['reconciliation_matched', 'reconciliation_discrepancy'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['auditable'])
            ->get();

        // Summarize results
        $report['summary'] = [
            'total_reconciled' => $reconciliationLogs->where('action', 'reconciliation_matched')->count(),
            'total_discrepancies' => $reconciliationLogs->where('action', 'reconciliation_discrepancy')->count(),
            'total_amount_reconciled' => $reconciliationLogs->where('action', 'reconciliation_matched')
                ->sum(function ($log) {
                    return $log->auditable?->amount ?? 0;
                }),
            'total_discrepancy_amount' => $reconciliationLogs->where('action', 'reconciliation_discrepancy')
                ->sum(function ($log) {
                    return abs($log->metadata['difference'] ?? 0);
                })
        ];

        // Detailed breakdown
        $report['details'] = $reconciliationLogs->map(function ($log) {
            return [
                'payment_id' => $log->model_id,
                'action' => $log->action,
                'amount' => $log->auditable?->amount,
                'metadata' => $log->metadata,
                'created_at' => $log->created_at
            ];
        });

        return $report;
    }

    /**
     * Auto-reconcile with SePay transactions
     */
    public function autoReconcileSePayTransactions(Carbon $date = null)
    {
        $date = $date ?? Carbon::today();

        // Get unreconciled SePay payments
        $payments = Payment::whereDate('payment_date', $date)
            ->where('payment_method', 'sepay')
            ->where('status', 'confirmed')
            ->whereNull('webhook_data->reconciled_at')
            ->get();

        $reconciledCount = 0;

        foreach ($payments as $payment) {
            if ($this->reconcileSePayPayment($payment)) {
                $reconciledCount++;
            }
        }

        Log::channel('payments')->info('SePay auto-reconciliation completed', [
            'date' => $date->toDateString(),
            'processed' => $payments->count(),
            'reconciled' => $reconciledCount
        ]);

        return [
            'processed' => $payments->count(),
            'reconciled' => $reconciledCount
        ];
    }

    /**
     * Reconcile individual SePay payment
     */
    private function reconcileSePayPayment(Payment $payment)
    {
        try {
            // Mark as reconciled in webhook_data
            $webhookData = $payment->webhook_data ?? [];
            $webhookData['reconciled_at'] = now()->toISOString();
            $webhookData['reconciliation_status'] = 'auto_reconciled';

            $payment->update(['webhook_data' => $webhookData]);

            // Log reconciliation
            AuditLog::log(
                'App\\Models\\Payment',
                $payment->id,
                'sepay_auto_reconciled',
                null,
                null,
                [
                    'reconciled_at' => now()->toISOString(),
                    'transaction_reference' => $payment->transaction_reference
                ]
            );

            return true;

        } catch (\Exception $e) {
            Log::error('SePay payment reconciliation failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check for suspicious payment patterns
     */
    public function detectSuspiciousPatterns(Carbon $date = null)
    {
        $date = $date ?? Carbon::today();
        $suspiciousPatterns = [];

        // Pattern 1: Multiple payments same amount same time
        $duplicateAmounts = Payment::whereDate('created_at', $date)
            ->select('amount', 'enrollment_id', DB::raw('COUNT(*) as count'))
            ->groupBy('amount', 'enrollment_id')
            ->having('count', '>', 1)
            ->get();

        if ($duplicateAmounts->count() > 0) {
            $suspiciousPatterns[] = [
                'type' => 'duplicate_amounts',
                'description' => 'Multiple payments with same amount for same enrollment',
                'data' => $duplicateAmounts
            ];
        }

        // Pattern 2: Payments without corresponding bank records
        $unreconciledPayments = Payment::whereDate('payment_date', $date)
            ->where('payment_method', 'bank_transfer')
            ->where('status', 'confirmed')
            ->whereDoesntHave('auditLogs', function ($query) {
                $query->where('action', 'reconciliation_matched');
            })
            ->count();

        if ($unreconciledPayments > 5) {
            $suspiciousPatterns[] = [
                'type' => 'high_unreconciled_count',
                'description' => 'High number of unreconciled bank transfer payments',
                'count' => $unreconciledPayments
            ];
        }

        return $suspiciousPatterns;
    }
}

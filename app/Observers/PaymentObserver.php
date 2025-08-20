<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\PaymentMetricsService;
use Illuminate\Support\Facades\Log;

class PaymentObserver
{
    /**
     * Handle the Payment "created" event.
     */
    public function created(Payment $payment): void
    {
        try {
            // Record metrics
            PaymentMetricsService::recordPaymentCreated($payment);
            
            Log::info('Payment created', [
                'payment_id' => $payment->id,
                'enrollment_id' => $payment->enrollment_id,
                'amount' => $payment->amount,
                'method' => $payment->payment_method
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in PaymentObserver::created', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the Payment "updated" event.
     */
    public function updated(Payment $payment): void
    {
        try {
            // Check if status changed to confirmed
            if ($payment->wasChanged('status') && $payment->status === 'confirmed') {
                $source = $payment->webhook_id ? 'webhook' : 'manual';
                PaymentMetricsService::recordPaymentConfirmed($payment, $source);
                
                Log::info('Payment confirmed', [
                    'payment_id' => $payment->id,
                    'enrollment_id' => $payment->enrollment_id,
                    'amount' => $payment->amount,
                    'method' => $payment->payment_method,
                    'source' => $source
                ]);
            }
            
            // Check if status changed to cancelled
            if ($payment->wasChanged('status') && $payment->status === 'cancelled') {
                Log::info('Payment cancelled', [
                    'payment_id' => $payment->id,
                    'enrollment_id' => $payment->enrollment_id,
                    'amount' => $payment->amount,
                    'method' => $payment->payment_method,
                    'cancelled_by' => $payment->cancelled_by
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Error in PaymentObserver::updated', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the Payment "deleted" event.
     */
    public function deleted(Payment $payment): void
    {
        try {
            Log::warning('Payment deleted', [
                'payment_id' => $payment->id,
                'enrollment_id' => $payment->enrollment_id,
                'amount' => $payment->amount,
                'method' => $payment->payment_method,
                'status' => $payment->status
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in PaymentObserver::deleted', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the Payment "restored" event.
     */
    public function restored(Payment $payment): void
    {
        try {
            Log::info('Payment restored', [
                'payment_id' => $payment->id,
                'enrollment_id' => $payment->enrollment_id,
                'amount' => $payment->amount,
                'method' => $payment->payment_method
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in PaymentObserver::restored', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the Payment "force deleted" event.
     */
    public function forceDeleted(Payment $payment): void
    {
        try {
            Log::critical('Payment force deleted', [
                'payment_id' => $payment->id,
                'enrollment_id' => $payment->enrollment_id,
                'amount' => $payment->amount,
                'method' => $payment->payment_method
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in PaymentObserver::forceDeleted', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PaymentReconciliationService;
use Carbon\Carbon;

class ReconcilePayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:reconcile 
                            {--date= : Date to reconcile (Y-m-d format, default: yesterday)}
                            {--auto-sepay : Auto reconcile SePay transactions only}
                            {--detect-suspicious : Detect suspicious patterns only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile payments with bank statements and detect suspicious patterns';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->option('date') 
            ? Carbon::parse($this->option('date'))
            : Carbon::yesterday();

        $this->info("Starting payment reconciliation for: {$date->toDateString()}");

        $reconciliationService = app(PaymentReconciliationService::class);

        try {
            // Auto reconcile SePay transactions
            if ($this->option('auto-sepay') || !$this->option('detect-suspicious')) {
                $this->info('Auto-reconciling SePay transactions...');
                $sePayResults = $reconciliationService->autoReconcileSePayTransactions($date);
                
                $this->info("SePay reconciliation completed:");
                $this->line("  - Processed: {$sePayResults['processed']}");
                $this->line("  - Reconciled: {$sePayResults['reconciled']}");
            }

            // Detect suspicious patterns
            if ($this->option('detect-suspicious') || !$this->option('auto-sepay')) {
                $this->info('Detecting suspicious patterns...');
                $suspiciousPatterns = $reconciliationService->detectSuspiciousPatterns($date);
                
                if (empty($suspiciousPatterns)) {
                    $this->info('No suspicious patterns detected.');
                } else {
                    $this->warn('Suspicious patterns detected:');
                    foreach ($suspiciousPatterns as $pattern) {
                        $this->line("  - {$pattern['type']}: {$pattern['description']}");
                        if (isset($pattern['count'])) {
                            $this->line("    Count: {$pattern['count']}");
                        }
                    }
                }
            }

            $this->info('Payment reconciliation completed successfully.');

        } catch (\Exception $e) {
            $this->error("Payment reconciliation failed: {$e->getMessage()}");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

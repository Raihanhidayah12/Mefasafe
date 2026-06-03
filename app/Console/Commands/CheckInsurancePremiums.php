<?php

namespace App\Console\Commands;

use App\Models\InsurancePolicy;
use App\Services\InsuranceBillingService;
use Illuminate\Console\Command;

class CheckInsurancePremiums extends Command
{
    protected $signature = 'insurance:check-premiums';

    protected $description = 'Mark insurance policies that are due or overdue for premium payment.';

    public function handle(InsuranceBillingService $billingService): int
    {
        $updated = 0;

        InsurancePolicy::query()
            ->whereNotNull('next_payment_due_date')
            ->where('payment_status', 'verified')
            ->whereDate('next_payment_due_date', '<=', now()->toDateString())
            ->chunkById(100, function ($policies) use ($billingService, &$updated): void {
                foreach ($policies as $policy) {
                    if ($billingService->refreshPolicyStatus($policy)) {
                        $updated++;
                    }
                }
            });

        $this->info("Updated {$updated} premium billing status records.");

        return self::SUCCESS;
    }
}

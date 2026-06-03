<?php

namespace App\Services;

use App\Models\InsurancePolicy;
use Carbon\Carbon;

class InsuranceBillingService
{
    public function initializeSchedule(InsurancePolicy $policy): void
    {
        if (! $policy->next_payment_due_date) {
            $policy->forceFill([
                'next_payment_due_date' => $policy->start_date ?: now()->toDateString(),
                'grace_period_days' => $policy->grace_period_days ?: 30,
                'billing_cycle' => $policy->billing_cycle ?: 'monthly',
            ])->save();
        }
    }

    public function markPaymentVerified(InsurancePolicy $policy): void
    {
        $baseDate = $policy->next_payment_due_date
            ? Carbon::parse($policy->next_payment_due_date)
            : Carbon::parse($policy->start_date ?: now());

        $nextDueDate = $this->addCycle($baseDate, $policy->billing_cycle ?: 'monthly');

        $policy->forceFill([
            'status' => 'active',
            'payment_status' => 'verified',
            'last_payment_at' => now(),
            'next_payment_due_date' => $nextDueDate->toDateString(),
            'premium_reminder_sent_at' => null,
            'overdue_reminder_sent_at' => null,
        ])->save();
    }

    public function refreshPolicyStatus(InsurancePolicy $policy): bool
    {
        if (! $policy->next_payment_due_date || $policy->payment_status !== 'verified') {
            return false;
        }

        $today = Carbon::today();
        $dueDate = Carbon::parse($policy->next_payment_due_date)->startOfDay();
        $graceEndsAt = $dueDate->copy()->addDays((int) ($policy->grace_period_days ?? 30));

        if ($dueDate->greaterThan($today)) {
            return false;
        }

        $updates = [
            'payment_status' => 'pending',
            'payment_proof_path' => null,
        ];

        if ($today->greaterThan($graceEndsAt)) {
            $updates['status'] = 'inactive';
            $updates['overdue_reminder_sent_at'] = now();
        } else {
            $updates['premium_reminder_sent_at'] = now();
        }

        $policy->forceFill($updates)->save();

        return true;
    }

    private function addCycle(Carbon $date, string $cycle): Carbon
    {
        return $cycle === 'yearly'
            ? $date->copy()->addYear()
            : $date->copy()->addMonth();
    }
}

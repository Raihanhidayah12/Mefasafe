<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('insurance_policies')
            ->whereNull('next_payment_due_date')
            ->update([
                'next_payment_due_date' => DB::raw('COALESCE(start_date, DATE(created_at))'),
                'grace_period_days' => 30,
                'billing_cycle' => 'monthly',
            ]);
    }

    public function down(): void
    {
        DB::table('insurance_policies')
            ->update([
                'next_payment_due_date' => null,
                'last_payment_at' => null,
                'premium_reminder_sent_at' => null,
                'overdue_reminder_sent_at' => null,
            ]);
    }
};

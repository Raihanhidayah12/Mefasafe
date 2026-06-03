<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insurance_policies', function (Blueprint $table): void {
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly')->after('payment_status');
            $table->unsignedSmallInteger('grace_period_days')->default(30)->after('billing_cycle');
            $table->date('next_payment_due_date')->nullable()->after('grace_period_days');
            $table->timestamp('last_payment_at')->nullable()->after('next_payment_due_date');
            $table->timestamp('premium_reminder_sent_at')->nullable()->after('last_payment_at');
            $table->timestamp('overdue_reminder_sent_at')->nullable()->after('premium_reminder_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('insurance_policies', function (Blueprint $table): void {
            $table->dropColumn([
                'billing_cycle',
                'grace_period_days',
                'next_payment_due_date',
                'last_payment_at',
                'premium_reminder_sent_at',
                'overdue_reminder_sent_at',
            ]);
        });
    }
};

<?php

namespace App\Models;

use App\Models\Claim;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsurancePolicy extends Model
{
    protected $fillable = [
        'user_id',
        'policy_number',
        'insurance_type',
        'insured_name',
        'premium_amount',
        'coverage_limit',
        'start_date',
        'end_date',
        'status',
        'payment_method',
        'payment_proof_path',
        'payment_status',
        'billing_cycle',
        'grace_period_days',
        'next_payment_due_date',
        'last_payment_at',
        'premium_reminder_sent_at',
        'overdue_reminder_sent_at',
        'promo_code',
        'original_premium_amount',
        'discount_amount',
    ];

    protected $casts = [
        'premium_amount'  => 'decimal:2',
        'original_premium_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'coverage_limit'  => 'decimal:2',
        'start_date'      => 'date',
        'end_date'        => 'date',
        'next_payment_due_date' => 'date',
        'last_payment_at' => 'datetime',
        'premium_reminder_sent_at' => 'datetime',
        'overdue_reminder_sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class);
    }
}

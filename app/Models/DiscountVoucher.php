<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscountVoucher extends Model
{
    protected $fillable = [
        'code',
        'type',
        'value',
        'currency',
        'min_subtotal',
        'max_discount',
        'applies_to_plans',
        'billing_cycles',
        'first_purchase_only',
        'max_redemptions',
        'per_company_limit',
        'redeemed_count',
        'starts_at',
        'ends_at',
        'is_active',
        'description',
    ];

    protected $casts = [
        'applies_to_plans' => 'array',
        'billing_cycles' => 'array',
        'first_purchase_only' => 'boolean',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function redemptions(): HasMany
    {
        return $this->hasMany(VoucherRedemption::class, 'voucher_id');
    }
}


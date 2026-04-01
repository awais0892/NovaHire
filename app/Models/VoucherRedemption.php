<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherRedemption extends Model
{
    protected $fillable = [
        'voucher_id',
        'company_id',
        'user_id',
        'checkout_session_id',
        'subtotal',
        'discount_amount',
        'currency',
        'metadata',
        'redeemed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'redeemed_at' => 'datetime',
    ];

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(DiscountVoucher::class, 'voucher_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}


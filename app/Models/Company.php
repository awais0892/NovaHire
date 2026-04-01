<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'email', 'logo',
        'phone', 'website', 'plan', 'status', 'trial_ends_at',
        'stripe_customer_id', 'stripe_subscription_id', 'stripe_price_id',
        'billing_status', 'billing_cycle', 'billing_period_ends_at',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'billing_period_ends_at' => 'datetime',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function interviewSlots()
    {
        return $this->hasMany(InterviewSlot::class);
    }

    public function interviewSlotSettings()
    {
        return $this->hasOne(InterviewSlotSetting::class);
    }

    public function interviewSlotExceptions()
    {
        return $this->hasMany(InterviewSlotException::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'domain',
        'status',
        'plan',
        'environment',
        'industry',
        'country',
        'email',
        'phone',
    ];

    public function domains()
    {
        return $this->hasMany(CompanyDomain::class);
    }

    public function settings()
    {
        return $this->hasOne(CompanySetting::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)->whereIn('status', ['trialing', 'active', 'past_due']);
    }

    public function currentPlan(): ?Plan
    {
        return $this->activeSubscription?->plan;
    }
}

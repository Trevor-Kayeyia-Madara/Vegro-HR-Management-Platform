<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'price',
        'price_monthly',
        'price_annual',
        'employee_limit',
        'description',
        'currency',
        'interval',
        'is_active',
        'features',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'price_monthly' => 'decimal:2',
        'price_annual' => 'decimal:2',
        'employee_limit' => 'integer',
        'is_active' => 'boolean',
        'features' => 'array',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function planFeatures()
    {
        return $this->hasMany(PlanFeature::class);
    }

    public function featuresCatalog()
    {
        return $this->belongsToMany(Feature::class, 'plan_features')
            ->withPivot('config')
            ->withTimestamps();
    }
}

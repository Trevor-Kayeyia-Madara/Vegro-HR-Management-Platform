<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    protected $fillable = [
        'name',
        'key',
        'description',
    ];

    public function planFeatures()
    {
        return $this->hasMany(PlanFeature::class);
    }

    public function plans()
    {
        return $this->belongsToMany(Plan::class, 'plan_features')
            ->withPivot('config')
            ->withTimestamps();
    }
}


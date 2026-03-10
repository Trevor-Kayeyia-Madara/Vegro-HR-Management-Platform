<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxProfile extends Model
{
    protected $fillable = [
        'name',
        'country_code',
        'currency',
        'paye_bands',
        'personal_relief',
        'insurance_relief_rate',
        'insurance_relief_cap',
        'pension_cap',
        'mortgage_cap',
        'nssf_rate',
        'nssf_tier1_limit',
        'nssf_tier2_limit',
        'nssf_max',
        'shif_rate',
        'shif_min',
        'housing_levy_rate',
    ];

    protected $casts = [
        'paye_bands' => 'array',
    ];
}

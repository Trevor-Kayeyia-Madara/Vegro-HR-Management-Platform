<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class LeaveTypeSetting extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'type',
        'label',
        'enabled',
        'unit',
        'days_per_year',
        'full_pay_days',
        'half_pay_days',
        'accrual_per_month',
        'min_months_of_service',
        'notice_days',
        'requires_documentation',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'requires_documentation' => 'boolean',
        'days_per_year' => 'float',
        'full_pay_days' => 'float',
        'half_pay_days' => 'float',
        'accrual_per_month' => 'float',
    ];
}


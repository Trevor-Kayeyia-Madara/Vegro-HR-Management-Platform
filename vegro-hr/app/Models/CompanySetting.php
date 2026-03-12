<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'currency',
        'timezone',
        'locale',
        'date_format',
        'time_format',
        'tax_rules',
        'payroll_rules',
        'branding',
    ];

    protected $casts = [
        'tax_rules' => 'array',
        'payroll_rules' => 'array',
        'branding' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}

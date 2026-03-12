<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class CompanyDomain extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'domain',
        'is_primary',
        'verified_at',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class ComplianceAlert extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'code',
        'severity',
        'title',
        'message',
        'data',
        'detected_at',
        'acknowledged_by',
        'acknowledged_at',
    ];

    protected $casts = [
        'data' => 'array',
        'detected_at' => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function acknowledgedBy()
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'company_id',
        'actor_user_id',
        'action',
        'entity_type',
        'entity_id',
        'metadata',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}

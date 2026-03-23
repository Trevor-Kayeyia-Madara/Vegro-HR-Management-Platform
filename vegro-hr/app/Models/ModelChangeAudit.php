<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class ModelChangeAudit extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'actor_user_id',
        'entity_type',
        'entity_id',
        'action',
        'before_data',
        'after_data',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'before_data' => 'array',
        'after_data' => 'array',
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

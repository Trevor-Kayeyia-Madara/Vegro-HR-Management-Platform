<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AtsApplicationStageEvent extends Model
{
    use HasFactory;
    use BelongsToCompany;

    protected $table = 'ats_application_stage_events';

    protected $fillable = [
        'company_id',
        'application_id',
        'changed_by_user_id',
        'from_stage',
        'to_stage',
        'changed_at',
        'metadata',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function application()
    {
        return $this->belongsTo(AtsApplication::class, 'application_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}


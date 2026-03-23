<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\AuditsModelChanges;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AtsApplication extends Model
{
    use HasFactory;
    use BelongsToCompany;
    use AuditsModelChanges;

    protected $table = 'ats_applications';

    protected $fillable = [
        'company_id',
        'job_posting_id',
        'candidate_id',
        'created_by_user_id',
        'stage',
        'applied_at',
        'rating',
        'last_stage_changed_at',
    ];

    protected $casts = [
        'applied_at' => 'datetime',
        'last_stage_changed_at' => 'datetime',
    ];

    public function job()
    {
        return $this->belongsTo(AtsJobPosting::class, 'job_posting_id');
    }

    public function candidate()
    {
        return $this->belongsTo(AtsCandidate::class, 'candidate_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function notes()
    {
        return $this->hasMany(AtsApplicationNote::class, 'application_id');
    }

    public function stageEvents()
    {
        return $this->hasMany(AtsApplicationStageEvent::class, 'application_id');
    }
}

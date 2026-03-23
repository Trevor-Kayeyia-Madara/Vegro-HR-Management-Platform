<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\AuditsModelChanges;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AtsCandidate extends Model
{
    use HasFactory;
    use BelongsToCompany;
    use AuditsModelChanges;

    protected $table = 'ats_candidates';

    protected $fillable = [
        'company_id',
        'created_by_user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'source',
        'linkedin_url',
        'notes',
        'consent_at',
    ];

    protected $casts = [
        'consent_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function applications()
    {
        return $this->hasMany(AtsApplication::class, 'candidate_id');
    }

    public function getFullNameAttribute(): string
    {
        $name = trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
        return $name !== '' ? $name : ($this->first_name ?? '');
    }
}

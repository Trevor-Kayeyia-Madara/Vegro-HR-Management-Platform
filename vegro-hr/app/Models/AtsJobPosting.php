<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\AuditsModelChanges;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AtsJobPosting extends Model
{
    use HasFactory;
    use BelongsToCompany;
    use AuditsModelChanges;

    protected $table = 'ats_job_postings';

    protected $fillable = [
        'company_id',
        'department_id',
        'hiring_manager_user_id',
        'created_by_user_id',
        'title',
        'employment_type',
        'location',
        'currency',
        'salary_min',
        'salary_max',
        'openings',
        'status',
        'description',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function hiringManager()
    {
        return $this->belongsTo(User::class, 'hiring_manager_user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function applications()
    {
        return $this->hasMany(AtsApplication::class, 'job_posting_id');
    }
}

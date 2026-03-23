<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectMembership extends Model
{
    use HasFactory;
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'project_id',
        'employee_id',
        'reports_to_user_id',
        'role_title',
        'allocation_percent',
        'start_date',
        'end_date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function reportsTo()
    {
        return $this->belongsTo(User::class, 'reports_to_user_id');
    }
}


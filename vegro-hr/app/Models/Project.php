<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'status',
        'start_date',
        'end_date',
    ];

    public function memberships()
    {
        return $this->hasMany(ProjectMembership::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}


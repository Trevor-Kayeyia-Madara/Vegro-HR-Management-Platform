<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\AuditsModelChanges;

class LeaveRequest extends Model
{
    use HasFactory;
    use BelongsToCompany;
    use AuditsModelChanges;

    protected $fillable = [
        'company_id',
        'employee_id',
        'type',
        'start_date',
        'end_date',
        'reason',
        'leave_days',
        'status',
        'approved_by',
        'approved_role',
        'approved_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class EmployeeLeaveBalance extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'employee_id',
        'leave_type',
        'entitled_days',
        'used_days',
        'balance_days',
        'carry_forward_days',
        'last_reset_at',
    ];

    protected $casts = [
        'entitled_days' => 'float',
        'used_days' => 'float',
        'balance_days' => 'float',
        'carry_forward_days' => 'float',
        'last_reset_at' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}

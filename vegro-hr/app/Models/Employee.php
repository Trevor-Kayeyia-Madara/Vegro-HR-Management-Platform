<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_number','user_id','name','email','phone',
        'department_id','position','salary','hire_date','status',
        'annual_leave_days','annual_leave_used','annual_leave_balance'
    ];

    public function department() { return $this->belongsTo(Department::class); }
    public function payrolls() { return $this->hasMany(Payroll::class); }
    public function attendance() { return $this->hasMany(Attendance::class); }
    public function leaveRequests() { return $this->hasMany(LeaveRequest::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function roles() { return $this->belongsToMany(Role::class); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'month',
        'year',
        'basic_salary',
        'allowances',
        'deductions',
        'tax',
        'net_salary'
    ];

    // Relationship: Payroll belongs to Employee
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // Relationship: Payroll has one Payslip
    public function payslip()
    {
        return $this->hasOne(Payslip::class);
    }
}
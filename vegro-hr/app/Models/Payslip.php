<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payslip extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_id',
        'employee_id',
        'employee_name',
        'employee_email',
        'employee_number',
        'pay_period_start',
        'pay_period_end',
        'gross_pay',
        'total_deductions',
        'net_pay',
        'earnings_breakdown',
        'deductions_breakdown',
        'status',
        'approved_by',
        'approved_at',
        'issued_at',
        'pdf_path',
        'generated_at',
    ];

    protected $casts = [
        'earnings_breakdown' => 'array',
        'deductions_breakdown' => 'array',
        'pay_period_start' => 'date',
        'pay_period_end' => 'date',
        'approved_at' => 'datetime',
        'issued_at' => 'datetime',
        'generated_at' => 'datetime',
    ];

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}

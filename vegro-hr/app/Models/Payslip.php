<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payslip extends Model
{
    use HasFactory;

    protected $fillable = ['payroll_id', 'pdf_path', 'generated_at'];

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }
}
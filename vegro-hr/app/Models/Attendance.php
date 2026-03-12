<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToCompany;

class Attendance extends Model
{
    use HasFactory;
    use BelongsToCompany;

    protected $fillable = ['company_id','employee_id','date','clock_in','clock_out','status'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}

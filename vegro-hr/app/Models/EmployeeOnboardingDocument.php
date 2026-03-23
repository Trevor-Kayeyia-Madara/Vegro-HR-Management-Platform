<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\AuditsModelChanges;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeOnboardingDocument extends Model
{
    use HasFactory;
    use BelongsToCompany;
    use AuditsModelChanges;

    protected $fillable = [
        'company_id',
        'employee_id',
        'template_id',
        'status',
        'due_date',
        'signed_at',
        'signature_name',
        'signed_ip',
        'signed_user_agent',
    ];

    protected $casts = [
        'due_date' => 'date',
        'signed_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function template()
    {
        return $this->belongsTo(OnboardingDocumentTemplate::class, 'template_id');
    }
}

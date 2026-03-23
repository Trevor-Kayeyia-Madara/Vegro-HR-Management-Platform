<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\AuditsModelChanges;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeFeedback extends Model
{
    use HasFactory;
    use BelongsToCompany;
    use AuditsModelChanges;

    protected $table = 'employee_feedback';

    protected $fillable = [
        'company_id',
        'submitted_by',
        'category',
        'subject',
        'message',
        'status',
        'response_message',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}

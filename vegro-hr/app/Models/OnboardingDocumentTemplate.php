<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\AuditsModelChanges;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnboardingDocumentTemplate extends Model
{
    use HasFactory;
    use BelongsToCompany;
    use AuditsModelChanges;

    protected $fillable = [
        'company_id',
        'title',
        'type',
        'content',
        'file_name',
        'file_path',
        'file_mime',
        'file_size',
        'requires_signature',
        'is_active',
    ];

    protected $casts = [
        'requires_signature' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function assignments()
    {
        return $this->hasMany(EmployeeOnboardingDocument::class, 'template_id');
    }
}

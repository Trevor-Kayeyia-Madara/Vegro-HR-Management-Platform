<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AtsApplicationNote extends Model
{
    use HasFactory;
    use BelongsToCompany;

    protected $table = 'ats_application_notes';

    protected $fillable = [
        'company_id',
        'application_id',
        'author_user_id',
        'note',
    ];

    public function application()
    {
        return $this->belongsTo(AtsApplication::class, 'application_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }
}


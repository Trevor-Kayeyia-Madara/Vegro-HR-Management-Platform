<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToCompany;

class DashboardDefinition extends Model
{
    use HasFactory;
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'user_id',
        'name',
        'description',
    ];

    public function widgets()
    {
        return $this->hasMany(DashboardWidget::class, 'dashboard_id')->orderBy('position');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

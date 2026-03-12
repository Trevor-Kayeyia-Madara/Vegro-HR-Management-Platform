<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToCompany;

class DashboardWidget extends Model
{
    use HasFactory;
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'dashboard_id',
        'title',
        'source',
        'chart_type',
        'columns',
        'filters',
        'sort',
        'limit',
        'x_field',
        'y_field',
        'aggregate',
        'width',
        'height',
        'position',
    ];

    protected $casts = [
        'columns' => 'array',
        'filters' => 'array',
        'sort' => 'array',
    ];

    public function dashboard()
    {
        return $this->belongsTo(DashboardDefinition::class, 'dashboard_id');
    }
}

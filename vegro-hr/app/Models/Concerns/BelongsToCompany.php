<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            $companyId = App::has('company_id') ? App::get('company_id') : null;
            if ($companyId) {
                $builder->where($builder->getModel()->getTable() . '.company_id', $companyId);
            }
        });

        static::creating(function ($model) {
            $companyId = App::has('company_id') ? App::get('company_id') : null;
            if ($companyId && empty($model->company_id)) {
                $model->company_id = $companyId;
            }
        });
    }
}

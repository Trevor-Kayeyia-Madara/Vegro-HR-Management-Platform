<?php

use App\Models\Company;
use App\Services\FeatureGateService;

if (!function_exists('feature')) {
    function feature(?Company $company = null): FeatureGateService
    {
        return new FeatureGateService($company);
    }
}


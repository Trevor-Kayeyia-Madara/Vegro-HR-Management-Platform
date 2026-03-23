<?php

namespace App\Services;

use App\Models\Company;
use App\Models\PlanFeature;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FeatureGateService
{
    protected ?Company $company;

    /** @var array<string, array{enabled: bool, config: array}> */
    protected array $features = [];

    public function __construct(?Company $company = null)
    {
        $this->company = $company ?? (app()->bound('currentCompany') ? app('currentCompany') : null);
        $this->bootFeatures();
    }

    protected function bootFeatures(): void
    {
        if (!$this->company) {
            return;
        }

        $subscription = $this->company->activeSubscription()
            ->with(['plan.planFeatures.feature'])
            ->latest('id')
            ->first();

        $plan = $subscription?->plan;
        if (!$plan) {
            return;
        }

        $planFeatures = $plan->planFeatures;
        if ($planFeatures->isNotEmpty()) {
            /** @var PlanFeature $planFeature */
            foreach ($planFeatures as $planFeature) {
                $key = $planFeature->feature?->key;
                if (!$key) {
                    continue;
                }

                $this->features[$key] = [
                    'enabled' => true,
                    'config' => is_array($planFeature->config) ? $planFeature->config : [],
                ];
            }

            return;
        }

        $legacy = is_array($plan->features) ? $plan->features : [];
        foreach ($legacy as $key => $value) {
            if (is_int($key) && is_string($value)) {
                $this->features[$value] = ['enabled' => true, 'config' => []];
                continue;
            }

            if (is_string($key)) {
                $this->features[$key] = [
                    'enabled' => (bool) ($value['enabled'] ?? true),
                    'config' => is_array($value) ? $value : [],
                ];
            }
        }
    }

    public function has(string $key): bool
    {
        return isset($this->features[$key]) && $this->features[$key]['enabled'] === true;
    }

    public function get(string $key): array
    {
        return $this->features[$key]['config'] ?? [];
    }

    public function enforce(string $key): void
    {
        if (!$this->has($key)) {
            throw new HttpException(403, 'Upgrade your plan to access this feature.');
        }
    }
}


<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Plan;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    protected ActivityLogService $activity;

    public function __construct(ActivityLogService $activity)
    {
        $this->activity = $activity;
    }

    public function index()
    {
        return ApiResponse::success(Plan::orderBy('name')->get(), 'Plans retrieved successfully');
    }

    public function publicIndex()
    {
        $plans = Plan::query()
            ->where('is_active', true)
            ->orderByRaw("FIELD(slug, 'starter', 'growth', 'pro', 'enterprise')")
            ->orderBy('id')
            ->get([
                'id',
                'name',
                'slug',
                'description',
                'price_monthly',
                'price_annual',
                'employee_limit',
                'currency',
            ]);

        return ApiResponse::success($plans, 'Public plans retrieved successfully');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:plans,name',
            'slug' => 'nullable|string|max:255|unique:plans,slug',
            'price_monthly' => 'nullable|numeric|min:0',
            'price_annual' => 'nullable|numeric|min:0',
            'employee_limit' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'interval' => ['nullable', Rule::in(['monthly', 'yearly', 'one_time'])],
            'is_active' => 'sometimes|boolean',
            'features' => 'sometimes|array',
        ]);

        $plan = Plan::create($validated);
        $this->activity->log('plan.created', null, Plan::class, $plan->id);

        return ApiResponse::success(['plan' => $plan], 'Plan created successfully', 201);
    }

    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('plans', 'name')->ignore($plan->id)],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('plans', 'slug')->ignore($plan->id)],
            'price_monthly' => 'sometimes|nullable|numeric|min:0',
            'price_annual' => 'sometimes|nullable|numeric|min:0',
            'employee_limit' => 'sometimes|nullable|integer|min:1',
            'description' => 'sometimes|nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'currency' => 'sometimes|string|max:10',
            'interval' => ['sometimes', Rule::in(['monthly', 'yearly', 'one_time'])],
            'is_active' => 'sometimes|boolean',
            'features' => 'sometimes|array',
        ]);

        $plan->update($validated);
        $this->activity->log('plan.updated', null, Plan::class, $plan->id);

        return ApiResponse::success(['plan' => $plan->fresh()], 'Plan updated successfully');
    }
}

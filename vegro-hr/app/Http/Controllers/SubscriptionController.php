<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    protected ActivityLogService $activity;

    public function __construct(ActivityLogService $activity)
    {
        $this->activity = $activity;
    }

    public function index(Request $request)
    {
        $query = Subscription::with(['company', 'plan'])->orderBy('created_at', 'desc');

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->query('company_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $perPage = max((int) $request->query('per_page', 50), 1);
        return ApiResponse::success($query->paginate($perPage), 'Subscriptions retrieved successfully');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'plan_id' => 'required|integer|exists:plans,id',
            'billing_cycle' => ['nullable', Rule::in(['monthly', 'annual'])],
            'status' => ['nullable', Rule::in(['trialing', 'active', 'past_due', 'canceled', 'paused'])],
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
            'trial_ends_at' => 'nullable|date',
            'cancel_at' => 'nullable|date',
            'metadata' => 'sometimes|array',
        ]);

        $subscription = Subscription::create($validated);
        $company = Company::find($validated['company_id']);
        $plan = Plan::find($validated['plan_id']);
        if ($company && $plan) {
            $company->update(['plan' => $plan->name]);
        }

        $this->activity->log('subscription.created', $validated['company_id'], Subscription::class, $subscription->id);

        return ApiResponse::success(['subscription' => $subscription], 'Subscription created successfully', 201);
    }

    public function update(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'plan_id' => 'sometimes|integer|exists:plans,id',
            'billing_cycle' => ['sometimes', Rule::in(['monthly', 'annual'])],
            'status' => ['sometimes', Rule::in(['trialing', 'active', 'past_due', 'canceled', 'paused'])],
            'starts_at' => 'sometimes|date',
            'ends_at' => 'sometimes|date',
            'trial_ends_at' => 'sometimes|date',
            'cancel_at' => 'sometimes|date',
            'metadata' => 'sometimes|array',
        ]);

        $subscription->update($validated);

        if (!empty($validated['plan_id'])) {
            $plan = Plan::find($validated['plan_id']);
            if ($plan) {
                $subscription->company?->update(['plan' => $plan->name]);
            }
        }

        $this->activity->log('subscription.updated', $subscription->company_id, Subscription::class, $subscription->id);

        return ApiResponse::success(['subscription' => $subscription->fresh()], 'Subscription updated successfully');
    }
}

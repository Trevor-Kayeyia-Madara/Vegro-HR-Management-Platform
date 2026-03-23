<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\AtsJobPosting;
use App\Services\AtsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AtsJobPostingController extends Controller
{
    public function __construct(protected AtsService $atsService) {}

    public function index(Request $request)
    {
        $perPage = max((int) $request->query('per_page', 10), 1);
        $status = $request->query('status');

        $query = AtsJobPosting::with(['department:id,name', 'hiringManager:id,name,email'])
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        return ApiResponse::success($query->paginate($perPage), 'Job postings retrieved');
    }

    public function store(Request $request)
    {
        $companyId = $request->attributes->get('company_id') ?? auth()->user()?->company_id;

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where('company_id', $companyId),
            ],
            'hiring_manager_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('company_id', $companyId),
            ],
            'employment_type' => ['nullable', Rule::in(AtsService::EMPLOYMENT_TYPES)],
            'location' => 'nullable|string|max:255',
            'currency' => 'nullable|string|max:10',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0',
            'openings' => 'nullable|integer|min:1|max:999',
            'status' => ['nullable', Rule::in(AtsService::JOB_STATUSES)],
            'description' => 'nullable|string|max:20000',
        ]);

        if (!empty($validated['salary_min']) && !empty($validated['salary_max']) && $validated['salary_max'] < $validated['salary_min']) {
            return ApiResponse::error('salary_max must be greater than or equal to salary_min', 422);
        }

        $job = $this->atsService->createJob($validated, (int) $companyId, auth()->id());
        $job->load(['department:id,name', 'hiringManager:id,name,email']);

        return ApiResponse::success($job, 'Job posting created', 201);
    }

    public function show(AtsJobPosting $jobPosting)
    {
        $jobPosting->load(['department:id,name', 'hiringManager:id,name,email']);
        $jobPosting->loadCount('applications');
        return ApiResponse::success($jobPosting, 'Job posting retrieved');
    }

    public function update(Request $request, AtsJobPosting $jobPosting)
    {
        $companyId = $request->attributes->get('company_id') ?? auth()->user()?->company_id;

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where('company_id', $companyId),
            ],
            'hiring_manager_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('company_id', $companyId),
            ],
            'employment_type' => ['nullable', Rule::in(AtsService::EMPLOYMENT_TYPES)],
            'location' => 'nullable|string|max:255',
            'currency' => 'nullable|string|max:10',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0',
            'openings' => 'nullable|integer|min:1|max:999',
            'status' => ['nullable', Rule::in(AtsService::JOB_STATUSES)],
            'description' => 'nullable|string|max:20000',
        ]);

        if (array_key_exists('salary_min', $validated) && array_key_exists('salary_max', $validated)) {
            $min = $validated['salary_min'];
            $max = $validated['salary_max'];
            if ($min !== null && $max !== null && $max < $min) {
                return ApiResponse::error('salary_max must be greater than or equal to salary_min', 422);
            }
        }

        $job = $this->atsService->updateJob($jobPosting, $validated, (int) $companyId);
        $job->load(['department:id,name', 'hiringManager:id,name,email']);

        return ApiResponse::success($job, 'Job posting updated');
    }

    public function destroy(Request $request, AtsJobPosting $jobPosting)
    {
        $companyId = $request->attributes->get('company_id') ?? auth()->user()?->company_id;
        $jobPosting->delete();

        app(\App\Services\ActivityLogService::class)->log(
            'ats.job.deleted',
            (int) $companyId,
            AtsJobPosting::class,
            $jobPosting->id,
            ['title' => $jobPosting->title]
        );

        return ApiResponse::success(null, 'Job posting deleted');
    }
}


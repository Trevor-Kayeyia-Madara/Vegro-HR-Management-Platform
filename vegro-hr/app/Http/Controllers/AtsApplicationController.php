<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\AtsApplication;
use App\Models\AtsApplicationNote;
use App\Models\AtsJobPosting;
use App\Models\AtsCandidate;
use App\Services\AtsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AtsApplicationController extends Controller
{
    public function __construct(protected AtsService $atsService) {}

    public function index(Request $request)
    {
        $perPage = max((int) $request->query('per_page', 10), 1);
        $stage = $request->query('stage');
        $jobId = $request->query('job_posting_id');

        $query = AtsApplication::with([
            'job:id,title,status',
            'candidate:id,first_name,last_name,email,phone,source',
        ])->orderBy('created_at', 'desc');

        if ($stage) {
            $query->where('stage', $stage);
        }
        if ($jobId) {
            $query->where('job_posting_id', (int) $jobId);
        }

        return ApiResponse::success($query->paginate($perPage), 'Applications retrieved');
    }

    public function store(Request $request)
    {
        $companyId = $request->attributes->get('company_id') ?? auth()->user()?->company_id;

        $validated = $request->validate([
            'job_posting_id' => [
                'required',
                'integer',
                Rule::exists('ats_job_postings', 'id')->where('company_id', $companyId),
            ],
            'candidate_id' => [
                'required',
                'integer',
                Rule::exists('ats_candidates', 'id')->where('company_id', $companyId),
            ],
            'stage' => ['nullable', Rule::in(AtsService::STAGES)],
        ]);

        $application = $this->atsService->createApplication(
            (int) $companyId,
            (int) $validated['job_posting_id'],
            (int) $validated['candidate_id'],
            auth()->id(),
            $validated['stage'] ?? null
        );

        $application->load([
            'job:id,title,status',
            'candidate:id,first_name,last_name,email,phone,source',
        ]);

        return ApiResponse::success($application, 'Application created', 201);
    }

    public function show(AtsApplication $application)
    {
        $application->load([
            'job:id,title,status',
            'candidate:id,first_name,last_name,email,phone,source,linkedin_url',
            'notes.author:id,name,email',
            'stageEvents.changedBy:id,name,email',
        ]);

        return ApiResponse::success($application, 'Application retrieved');
    }

    public function update(Request $request, AtsApplication $application)
    {
        $validated = $request->validate([
            'stage' => ['nullable', Rule::in(AtsService::STAGES)],
            'rating' => 'nullable|integer|min:1|max:5',
        ]);

        $companyId = (int) ($request->attributes->get('company_id') ?? auth()->user()?->company_id);

        if (!empty($validated['stage'])) {
            $application = $this->atsService->changeStage($application, $companyId, auth()->id(), $validated['stage']);
        }

        if (array_key_exists('rating', $validated)) {
            $application->update(['rating' => $validated['rating']]);
            app(\App\Services\ActivityLogService::class)->log(
                'ats.application.rating_updated',
                $companyId,
                AtsApplication::class,
                $application->id,
                ['rating' => $validated['rating']]
            );
        }

        $application->load(['job:id,title,status', 'candidate:id,first_name,last_name,email,phone,source']);
        return ApiResponse::success($application, 'Application updated');
    }

    public function destroy(Request $request, AtsApplication $application)
    {
        $companyId = (int) ($request->attributes->get('company_id') ?? auth()->user()?->company_id);
        $application->delete();

        app(\App\Services\ActivityLogService::class)->log(
            'ats.application.deleted',
            $companyId,
            AtsApplication::class,
            $application->id
        );

        return ApiResponse::success(null, 'Application deleted');
    }

    public function addNote(Request $request, AtsApplication $application)
    {
        $validated = $request->validate([
            'note' => 'required|string|max:20000',
        ]);

        $companyId = (int) ($request->attributes->get('company_id') ?? auth()->user()?->company_id);

        $note = $this->atsService->addNote($application, $companyId, auth()->id(), $validated['note']);
        $note->load('author:id,name,email');

        return ApiResponse::success($note, 'Note added', 201);
    }
}


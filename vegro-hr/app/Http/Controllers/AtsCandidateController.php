<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\AtsCandidate;
use App\Services\AtsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AtsCandidateController extends Controller
{
    public function __construct(protected AtsService $atsService) {}

    public function index(Request $request)
    {
        $perPage = max((int) $request->query('per_page', 10), 1);
        $q = trim((string) $request->query('q', ''));

        $query = AtsCandidate::orderBy('created_at', 'desc');
        if ($q !== '') {
            $query->where(function ($builder) use ($q) {
                $builder
                    ->where('first_name', 'like', '%' . $q . '%')
                    ->orWhere('last_name', 'like', '%' . $q . '%')
                    ->orWhere('email', 'like', '%' . $q . '%')
                    ->orWhere('phone', 'like', '%' . $q . '%');
            });
        }

        return ApiResponse::success($query->paginate($perPage), 'Candidates retrieved');
    }

    public function store(Request $request)
    {
        $companyId = $request->attributes->get('company_id') ?? auth()->user()?->company_id;

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('ats_candidates', 'email')->where('company_id', $companyId),
            ],
            'phone' => 'nullable|string|max:50',
            'source' => 'nullable|string|max:255',
            'linkedin_url' => 'nullable|url|max:500',
            'notes' => 'nullable|string|max:20000',
            'consent_at' => 'nullable|date',
        ]);

        $candidate = $this->atsService->createCandidate($validated, (int) $companyId, auth()->id());
        return ApiResponse::success($candidate, 'Candidate created', 201);
    }

    public function show(AtsCandidate $candidate)
    {
        $candidate->loadCount('applications');
        return ApiResponse::success($candidate, 'Candidate retrieved');
    }

    public function update(Request $request, AtsCandidate $candidate)
    {
        $companyId = $request->attributes->get('company_id') ?? auth()->user()?->company_id;

        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('ats_candidates', 'email')
                    ->where('company_id', $companyId)
                    ->ignore($candidate->id),
            ],
            'phone' => 'nullable|string|max:50',
            'source' => 'nullable|string|max:255',
            'linkedin_url' => 'nullable|url|max:500',
            'notes' => 'nullable|string|max:20000',
            'consent_at' => 'nullable|date',
        ]);

        $candidate = $this->atsService->updateCandidate($candidate, $validated, (int) $companyId);
        return ApiResponse::success($candidate, 'Candidate updated');
    }

    public function destroy(Request $request, AtsCandidate $candidate)
    {
        $companyId = $request->attributes->get('company_id') ?? auth()->user()?->company_id;
        $candidate->delete();

        app(\App\Services\ActivityLogService::class)->log(
            'ats.candidate.deleted',
            (int) $companyId,
            AtsCandidate::class,
            $candidate->id,
            ['candidate_id' => $candidate->id]
        );

        return ApiResponse::success(null, 'Candidate deleted');
    }
}

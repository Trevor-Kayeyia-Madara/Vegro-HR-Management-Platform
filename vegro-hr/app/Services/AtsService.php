<?php

namespace App\Services;

use App\Models\AtsApplication;
use App\Models\AtsApplicationNote;
use App\Models\AtsApplicationStageEvent;
use App\Models\AtsCandidate;
use App\Models\AtsJobPosting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AtsService
{
    public const STAGES = [
        'applied',
        'screening',
        'interview',
        'offer',
        'hired',
        'rejected',
        'withdrawn',
    ];

    public const EMPLOYMENT_TYPES = [
        'full_time',
        'part_time',
        'contract',
        'intern',
    ];

    public const JOB_STATUSES = [
        'draft',
        'open',
        'closed',
    ];

    public function __construct(
        protected ActivityLogService $activityLogService,
    ) {}

    public function createJob(array $payload, int $companyId, ?int $actorUserId): AtsJobPosting
    {
        $job = AtsJobPosting::create(array_merge($payload, [
            'company_id' => $companyId,
            'created_by_user_id' => $actorUserId,
        ]));

        $this->activityLogService->log(
            'ats.job.created',
            $companyId,
            AtsJobPosting::class,
            $job->id,
            ['title' => $job->title, 'status' => $job->status]
        );

        return $job;
    }

    public function updateJob(AtsJobPosting $job, array $payload, int $companyId): AtsJobPosting
    {
        $job->update($payload);

        $this->activityLogService->log(
            'ats.job.updated',
            $companyId,
            AtsJobPosting::class,
            $job->id,
            ['title' => $job->title, 'status' => $job->status]
        );

        return $job;
    }

    public function createCandidate(array $payload, int $companyId, ?int $actorUserId): AtsCandidate
    {
        $candidate = AtsCandidate::create(array_merge($payload, [
            'company_id' => $companyId,
            'created_by_user_id' => $actorUserId,
        ]));

        $this->activityLogService->log(
            'ats.candidate.created',
            $companyId,
            AtsCandidate::class,
            $candidate->id,
            ['candidate_id' => $candidate->id]
        );

        return $candidate;
    }

    public function updateCandidate(AtsCandidate $candidate, array $payload, int $companyId): AtsCandidate
    {
        $candidate->update($payload);

        $this->activityLogService->log(
            'ats.candidate.updated',
            $companyId,
            AtsCandidate::class,
            $candidate->id,
            ['candidate_id' => $candidate->id]
        );

        return $candidate;
    }

    public function createApplication(int $companyId, int $jobPostingId, int $candidateId, ?int $actorUserId, ?string $stage = null): AtsApplication
    {
        $stage = $stage ? strtolower($stage) : 'applied';
        if (!in_array($stage, self::STAGES, true)) {
            $stage = 'applied';
        }

        return DB::transaction(function () use ($companyId, $jobPostingId, $candidateId, $actorUserId, $stage) {
            $now = Carbon::now();

            $application = AtsApplication::create([
                'company_id' => $companyId,
                'job_posting_id' => $jobPostingId,
                'candidate_id' => $candidateId,
                'created_by_user_id' => $actorUserId,
                'stage' => $stage,
                'applied_at' => $now,
                'last_stage_changed_at' => $now,
            ]);

            AtsApplicationStageEvent::create([
                'company_id' => $companyId,
                'application_id' => $application->id,
                'changed_by_user_id' => $actorUserId,
                'from_stage' => null,
                'to_stage' => $stage,
                'changed_at' => $now,
                'metadata' => ['initial' => true],
            ]);

            $this->activityLogService->log(
                'ats.application.created',
                $companyId,
                AtsApplication::class,
                $application->id,
                ['stage' => $stage, 'job_posting_id' => $jobPostingId, 'candidate_id' => $candidateId]
            );

            return $application;
        });
    }

    public function changeStage(AtsApplication $application, int $companyId, ?int $actorUserId, string $toStage): AtsApplication
    {
        $toStage = strtolower(trim($toStage));
        if (!in_array($toStage, self::STAGES, true)) {
            return $application;
        }

        $fromStage = (string) $application->stage;
        if ($fromStage === $toStage) {
            return $application;
        }

        $now = Carbon::now();

        return DB::transaction(function () use ($application, $companyId, $actorUserId, $fromStage, $toStage, $now) {
            $application->update([
                'stage' => $toStage,
                'last_stage_changed_at' => $now,
            ]);

            AtsApplicationStageEvent::create([
                'company_id' => $companyId,
                'application_id' => $application->id,
                'changed_by_user_id' => $actorUserId,
                'from_stage' => $fromStage,
                'to_stage' => $toStage,
                'changed_at' => $now,
                'metadata' => null,
            ]);

            $this->activityLogService->log(
                'ats.application.stage_changed',
                $companyId,
                AtsApplication::class,
                $application->id,
                ['from' => $fromStage, 'to' => $toStage]
            );

            return $application;
        });
    }

    public function addNote(AtsApplication $application, int $companyId, ?int $actorUserId, string $note): AtsApplicationNote
    {
        $noteModel = AtsApplicationNote::create([
            'company_id' => $companyId,
            'application_id' => $application->id,
            'author_user_id' => $actorUserId,
            'note' => $note,
        ]);

        $this->activityLogService->log(
            'ats.application.note_added',
            $companyId,
            AtsApplicationNote::class,
            $noteModel->id,
            ['application_id' => $application->id]
        );

        return $noteModel;
    }
}

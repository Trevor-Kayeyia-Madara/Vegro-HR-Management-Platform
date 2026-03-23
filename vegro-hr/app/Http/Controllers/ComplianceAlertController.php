<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\ComplianceAlert;
use App\Services\ComplianceAlertService;
use Illuminate\Http\Request;

class ComplianceAlertController extends Controller
{
    public function __construct(protected ComplianceAlertService $complianceAlertService) {}

    public function index(Request $request)
    {
        $perPage = max((int) $request->query('per_page', 20), 1);
        $query = ComplianceAlert::query()
            ->with('acknowledgedBy:id,name,email')
            ->orderByRaw('CASE severity WHEN "high" THEN 1 WHEN "medium" THEN 2 ELSE 3 END')
            ->orderByDesc('detected_at');

        if ($request->filled('severity')) {
            $query->where('severity', $request->query('severity'));
        }
        if ($request->filled('open')) {
            $isOpen = filter_var($request->query('open'), FILTER_VALIDATE_BOOLEAN);
            if ($isOpen) {
                $query->whereNull('acknowledged_at');
            } else {
                $query->whereNotNull('acknowledged_at');
            }
        }

        return ApiResponse::success($query->paginate($perPage), 'Compliance alerts retrieved');
    }

    public function runScan(Request $request)
    {
        $companyId = (int) ($request->user()?->company_id ?? 0);
        if (!$companyId) {
            return ApiResponse::error('Company context not found', 422);
        }

        $result = $this->complianceAlertService->scanCompany($companyId);
        return ApiResponse::success($result, 'Compliance scan completed');
    }

    public function acknowledge(Request $request, ComplianceAlert $alert)
    {
        $user = $request->user();
        if (!$user) {
            return ApiResponse::unauthorized('Unauthorized');
        }

        $alert->update([
            'acknowledged_by' => $user->id,
            'acknowledged_at' => now(),
        ]);

        app(\App\Services\ActivityLogService::class)->log(
            'compliance.alert.acknowledged',
            (int) $user->company_id,
            ComplianceAlert::class,
            $alert->id,
            [
                'alert_id' => $alert->id,
                'code' => $alert->code,
                'severity' => $alert->severity,
            ]
        );

        return ApiResponse::success($alert->fresh('acknowledgedBy:id,name,email'), 'Compliance alert acknowledged');
    }
}

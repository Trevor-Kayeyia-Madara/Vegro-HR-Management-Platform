<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Services\LeaveService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaveTypeSettingController extends Controller
{
    public function __construct(protected LeaveService $leaveService)
    {
    }

    public function index(Request $request)
    {
        $companyId = $request->user()?->company_id;
        return ApiResponse::success($this->leaveService->getLeaveTypeSettings($companyId));
    }

    public function update(Request $request, string $type)
    {
        $payload = $request->validate([
            'label' => ['sometimes', 'string', 'max:120'],
            'enabled' => ['sometimes', 'boolean'],
            'unit' => ['sometimes', Rule::in(['working_days', 'calendar_days'])],
            'days_per_year' => ['nullable', 'numeric', 'min:0'],
            'full_pay_days' => ['nullable', 'numeric', 'min:0'],
            'half_pay_days' => ['nullable', 'numeric', 'min:0'],
            'accrual_per_month' => ['nullable', 'numeric', 'min:0'],
            'min_months_of_service' => ['sometimes', 'integer', 'min:0'],
            'notice_days' => ['sometimes', 'integer', 'min:0'],
            'requires_documentation' => ['sometimes', 'boolean'],
        ]);

        $companyId = $request->user()?->company_id;
        try {
            $updated = $this->leaveService->upsertLeaveTypeSetting($companyId, $type, $payload);
        } catch (\RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }

        return ApiResponse::success($updated, 'Leave type updated');
    }

    public function resetDefaults(Request $request)
    {
        $companyId = $request->user()?->company_id;
        $this->leaveService->ensureDefaultLeaveTypes($companyId, true);

        return ApiResponse::success(
            $this->leaveService->getLeaveTypeSettings($companyId),
            'Leave type defaults restored'
        );
    }
}

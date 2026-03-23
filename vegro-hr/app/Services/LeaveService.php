<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use App\Models\EmployeeManagerAssignment;
use App\Models\LeaveRequest;
use App\Models\LeaveTypeSetting;
use App\Models\User;
use App\Helpers\CsvHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LeaveService
{
    protected const DEFAULT_LEAVE_TYPES = [
        'annual' => [
            'label' => 'Annual Leave',
            'enabled' => true,
            'unit' => 'working_days',
            'days_per_year' => 21,
            'full_pay_days' => 21,
            'half_pay_days' => 0,
            'accrual_per_month' => 1.75,
            'min_months_of_service' => 12,
            'notice_days' => 0,
            'requires_documentation' => false,
        ],
        'maternity' => [
            'label' => 'Maternity Leave',
            'enabled' => true,
            'unit' => 'calendar_days',
            'days_per_year' => 91,
            'full_pay_days' => 91,
            'half_pay_days' => 0,
            'accrual_per_month' => null,
            'min_months_of_service' => 0,
            'notice_days' => 7,
            'requires_documentation' => false,
        ],
        'paternity' => [
            'label' => 'Paternity Leave',
            'enabled' => true,
            'unit' => 'calendar_days',
            'days_per_year' => 14,
            'full_pay_days' => 14,
            'half_pay_days' => 0,
            'accrual_per_month' => null,
            'min_months_of_service' => 0,
            'notice_days' => 0,
            'requires_documentation' => false,
        ],
        'sick' => [
            'label' => 'Sick Leave',
            'enabled' => true,
            'unit' => 'working_days',
            'days_per_year' => 28,
            'full_pay_days' => 14,
            'half_pay_days' => 14,
            'accrual_per_month' => null,
            'min_months_of_service' => 2,
            'notice_days' => 0,
            'requires_documentation' => true,
        ],
        'adoptive' => [
            'label' => 'Adoptive Leave',
            'enabled' => true,
            'unit' => 'calendar_days',
            'days_per_year' => 30,
            'full_pay_days' => 30,
            'half_pay_days' => 0,
            'accrual_per_month' => null,
            'min_months_of_service' => 0,
            'notice_days' => 0,
            'requires_documentation' => true,
        ],
        'compassionate' => [
            'label' => 'Compassionate Leave',
            'enabled' => true,
            'unit' => 'working_days',
            'days_per_year' => 7,
            'full_pay_days' => 7,
            'half_pay_days' => 0,
            'accrual_per_month' => null,
            'min_months_of_service' => 0,
            'notice_days' => 0,
            'requires_documentation' => false,
        ],
        'public_holiday' => [
            'label' => 'Public Holiday',
            'enabled' => true,
            'unit' => 'calendar_days',
            'days_per_year' => 365,
            'full_pay_days' => 365,
            'half_pay_days' => 0,
            'accrual_per_month' => null,
            'min_months_of_service' => 0,
            'notice_days' => 0,
            'requires_documentation' => false,
        ],
        'emergency' => [
            'label' => 'Emergency Leave',
            'enabled' => true,
            'unit' => 'working_days',
            'days_per_year' => 5,
            'full_pay_days' => 5,
            'half_pay_days' => 0,
            'accrual_per_month' => null,
            'min_months_of_service' => 0,
            'notice_days' => 0,
            'requires_documentation' => false,
        ],
    ];

    public function __construct(
        protected InAppNotificationService $notificationService
    ) {
    }

    public function requestLeave(array $data)
    {
        $payload = $data;
        $payload['type'] = strtolower(trim((string) ($payload['type'] ?? 'annual')));
        $payload['status'] = 'pending';

        $employeeId = $payload['employee_id'] ?? null;
        $employee = $employeeId ? Employee::find($employeeId) : null;
        if (!$employee) {
            throw new \RuntimeException('Employee not found.');
        }

        $companyId = $employee->company_id;
        $this->ensureDefaultLeaveTypes($companyId);

        $leaveType = LeaveTypeSetting::where('company_id', $companyId)
            ->where('type', $payload['type'])
            ->where('enabled', true)
            ->first();

        if (!$leaveType) {
            throw new \RuntimeException('Selected leave type is not enabled.');
        }

        $this->validateLeaveEligibility($employee, $leaveType);

        $payload['leave_days'] = $this->calculateLeaveDays(
            $payload['start_date'] ?? null,
            $payload['end_date'] ?? null,
            $leaveType->unit
        );

        if ($payload['leave_days'] <= 0) {
            throw new \RuntimeException('Leave period must be at least one day.');
        }

        if (!isset($payload['reason'])) {
            $payload['reason'] = $leaveType->label;
        }

        $balance = $this->getOrCreateEmployeeLeaveBalance($employee, $leaveType);
        if ((float) $payload['leave_days'] > (float) $balance->balance_days) {
            throw new \RuntimeException('Requested days exceed leave balance for this leave type.');
        }

        return DB::transaction(function () use ($payload, $employee, $leaveType, $companyId) {
            $leave = LeaveRequest::create($payload);
            $this->notifyApproversOnNewRequest($leave, $employee, $leaveType, $companyId);
            return $leave;
        });
    }

    public function approveLeave(LeaveRequest $leave, $userId)
    {
        return DB::transaction(function () use ($leave, $userId) {
            $leave->update([
                'status' => 'approved',
                'approved_by' => $userId,
                'approved_role' => auth()->user()?->role?->title,
                'approved_at' => now(),
            ]);

            $this->deductLeaveBalance($leave);
            $this->notifyEmployeeOnDecision($leave, true);

            return $leave;
        });
    }

    public function rejectLeave(LeaveRequest $leave, $userId)
    {
        $leave->update([
            'status' => 'rejected',
            'approved_by' => $userId,
            'approved_role' => auth()->user()?->role?->title,
            'approved_at' => now(),
        ]);

        $this->notifyEmployeeOnDecision($leave, false);

        return $leave;
    }

    public function getLeaveById($id)
    {
        return LeaveRequest::with(['employee', 'approver'])->findOrFail($id);
    }

    public function getLeavesByEmployee($employeeId)
    {
        return LeaveRequest::with(['employee', 'approver'])
            ->where('employee_id', $employeeId)
            ->get();
    }

    public function getPendingLeaves()
    {
        return LeaveRequest::with(['employee', 'approver'])
            ->where('status', 'pending')
            ->get();
    }

    public function getApprovedLeaves()
    {
        return LeaveRequest::with(['employee', 'approver'])
            ->where('status', 'approved')
            ->get();
    }

    public function getRejectedLeaves()
    {
        return LeaveRequest::with(['employee', 'approver'])
            ->where('status', 'rejected')
            ->get();
    }

    public function deleteLeave(LeaveRequest $leave)
    {
        return $leave->delete();
    }

    public function getLeavesWithPagination($perPage = 15)
    {
        return LeaveRequest::with(['employee', 'approver'])->paginate($perPage);
    }

    public function getLeavesByStatus($status)
    {
        return LeaveRequest::with(['employee', 'approver'])
            ->where('status', $status)
            ->get();
    }

    public function getAllLeaveRequests()
    {
        return LeaveRequest::with(['employee','approver'])->get();
    }

    public function getAllLeaveRequestsPaginated($perPage = 15)
    {
        return LeaveRequest::with(['employee', 'approver'])->paginate($perPage);
    }

    public function getLeavesForManagerPaginated($managerId, $perPage = 15)
    {
        $employeeIdsFromHierarchy = EmployeeManagerAssignment::where('manager_user_id', $managerId)
            ->whereIn('relationship_type', ['functional', 'dotted'])
            ->where(function ($query) {
                $query->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString());
            })
            ->pluck('employee_id');
        $departmentIds = Department::where('manager_id', $managerId)->pluck('id');

        return LeaveRequest::with(['employee', 'approver'])
            ->whereHas('employee', function ($query) use ($departmentIds, $employeeIdsFromHierarchy) {
                $query->whereIn('id', $employeeIdsFromHierarchy)
                    ->orWhereIn('department_id', $departmentIds);
            })
            ->paginate($perPage);
    }

    public function getLeavesForManagerByStatus($managerId, $status)
    {
        $employeeIdsFromHierarchy = EmployeeManagerAssignment::where('manager_user_id', $managerId)
            ->whereIn('relationship_type', ['functional', 'dotted'])
            ->where(function ($query) {
                $query->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString());
            })
            ->pluck('employee_id');
        $departmentIds = Department::where('manager_id', $managerId)->pluck('id');

        return LeaveRequest::with(['employee', 'approver'])
            ->where('status', $status)
            ->whereHas('employee', function ($query) use ($departmentIds, $employeeIdsFromHierarchy) {
                $query->whereIn('id', $employeeIdsFromHierarchy)
                    ->orWhereIn('department_id', $departmentIds);
            })
            ->get();
    }

    public function getLeaveStatistics()
    {
        return [
            'total' => LeaveRequest::count(),
            'pending' => LeaveRequest::where('status', 'pending')->count(),
            'approved' => LeaveRequest::where('status', 'approved')->count(),
            'rejected' => LeaveRequest::where('status', 'rejected')->count(),
        ];
    }

    public function getLeavesByDateRange($startDate, $endDate)
    {
        return LeaveRequest::whereBetween('start_date', [$startDate, $endDate])->get();
    }

    public function getLeavesByType($type)
    {
        return LeaveRequest::where('type', $type)->get();
    }

    public function getLeavesByDepartment($departmentId)
    {
        return LeaveRequest::whereHas('employee', function ($query) use ($departmentId) {
            $query->where('department_id', $departmentId);
        })->get();
    }

    public function getLeavesByApprover($approverId)
    {
        return LeaveRequest::where('approved_by', $approverId)->get();
    }

    public function exportLeavesToCSV()
    {
        $leaves = LeaveRequest::with(['employee', 'approver'])->get();
        $header = ['id', 'employee_id', 'type', 'start_date', 'end_date', 'leave_days', 'status', 'approved_by', 'approved_role'];
        $csvData = CsvHelper::row($header);

        foreach ($leaves as $leave) {
            $csvData .= CsvHelper::row([
                $leave->id,
                $leave->employee_id,
                $leave->type,
                CsvHelper::formatDate($leave->start_date),
                CsvHelper::formatDate($leave->end_date),
                $leave->leave_days,
                $leave->status,
                $leave->approved_by,
                $leave->approved_role,
            ]);
        }

        return $csvData;
    }

    public function getLeavesByEmployeeAndStatus($employeeId, $status)
    {
        return LeaveRequest::where('employee_id', $employeeId)
                            ->where('status', $status)
                            ->get();
    }

    public function getLeaveRequestsByStatus($status)
    {
        return LeaveRequest::with(['employee', 'approver'])
            ->where('status', $status)
            ->get();
    }

    public function getLeaveTypeSettings(?int $companyId)
    {
        $this->ensureDefaultLeaveTypes($companyId);

        return LeaveTypeSetting::where('company_id', $companyId)
            ->orderBy('type')
            ->get();
    }

    public function initializeLeaveBalancesForEmployee(Employee $employee, bool $force = false)
    {
        $companyId = $employee->company_id;
        $this->ensureDefaultLeaveTypes($companyId);

        $leaveTypes = LeaveTypeSetting::where('company_id', $companyId)
            ->where('enabled', true)
            ->get();

        $balances = collect();
        foreach ($leaveTypes as $leaveType) {
            $balance = $this->getOrCreateEmployeeLeaveBalance($employee, $leaveType);

            if ($force) {
                $entitled = $this->resolveEntitlementDays($employee, $leaveType);
                if (strtolower((string) $leaveType->type) === 'annual') {
                    $entitled += (float) ($balance->carry_forward_days ?? 0);
                }
                $balance->entitled_days = $entitled;
                $balance->used_days = min((float) $balance->used_days, (float) $entitled);
                $balance->balance_days = max((float) $entitled - (float) $balance->used_days, 0);
                $balance->save();
            }

            if (strtolower((string) $leaveType->type) === 'annual') {
                $this->syncAnnualColumnsToEmployee($employee, $balance);
            }

            $balances->push($balance->fresh());
        }

        return $balances;
    }

    public function syncAllEmployeeLeaveBalances(?int $companyId): int
    {
        $employees = Employee::where('company_id', $companyId)->get();
        $count = 0;
        foreach ($employees as $employee) {
            $this->initializeLeaveBalancesForEmployee($employee);
            $count++;
        }
        return $count;
    }

    public function upsertLeaveTypeSetting(?int $companyId, string $type, array $payload): LeaveTypeSetting
    {
        $type = strtolower(trim($type));
        $this->ensureDefaultLeaveTypes($companyId);

        $setting = LeaveTypeSetting::where('company_id', $companyId)
            ->where('type', $type)
            ->first();

        if (!$setting) {
            throw new \RuntimeException('Unknown leave type.');
        }

        $setting->fill($payload);
        $setting->save();

        $employees = Employee::where('company_id', $companyId)->get();
        foreach ($employees as $employee) {
            $this->initializeLeaveBalancesForEmployee($employee, true);
        }

        return $setting->refresh();
    }

    public function ensureDefaultLeaveTypes(?int $companyId, bool $force = false): void
    {
        foreach (self::DEFAULT_LEAVE_TYPES as $type => $defaults) {
            $existing = LeaveTypeSetting::where('company_id', $companyId)
                ->where('type', $type)
                ->first();

            if (!$existing) {
                LeaveTypeSetting::create(array_merge($defaults, [
                    'company_id' => $companyId,
                    'type' => $type,
                ]));
                continue;
            }

            if ($force) {
                $existing->fill($defaults);
                $existing->save();
            }
        }
    }

    protected function calculateLeaveDays($startDate, $endDate, string $unit = 'working_days'): int
    {
        if (!$startDate || !$endDate) {
            return 0;
        }
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();
        if ($end->lessThan($start)) {
            return 0;
        }

        if ($unit === 'calendar_days') {
            return $start->diffInDays($end) + 1;
        }

        $workingDays = 0;
        $cursor = $start->copy();
        while ($cursor->lessThanOrEqualTo($end)) {
            if (!$cursor->isWeekend()) {
                $workingDays++;
            }
            $cursor->addDay();
        }

        return $workingDays;
    }

    protected function validateLeaveEligibility(Employee $employee, LeaveTypeSetting $leaveType): void
    {
        $monthsInService = Carbon::parse($employee->hire_date)->diffInMonths(now());
        if ($monthsInService < (int) $leaveType->min_months_of_service) {
            throw new \RuntimeException(
                "You need at least {$leaveType->min_months_of_service} months of service for {$leaveType->label}."
            );
        }
    }

    protected function getOrCreateEmployeeLeaveBalance(Employee $employee, LeaveTypeSetting $leaveType): EmployeeLeaveBalance
    {
        $leaveTypeKey = strtolower((string) $leaveType->type);

        $balance = EmployeeLeaveBalance::firstOrNew([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'leave_type' => $leaveTypeKey,
        ]);

        if (!$balance->exists) {
            $entitled = $this->resolveEntitlementDays($employee, $leaveType);
            $used = $leaveTypeKey === 'annual' ? (float) ($employee->annual_leave_used ?? 0) : 0.0;

            $balance->fill([
                'entitled_days' => $entitled,
                'used_days' => $used,
                'balance_days' => max($entitled - $used, 0),
                'carry_forward_days' => 0,
                'last_reset_at' => $leaveTypeKey === 'annual' ? now()->startOfYear()->toDateString() : null,
            ]);
            $balance->save();
        } elseif ($leaveTypeKey === 'annual') {
            $currentYearStart = now()->startOfYear();
            $lastResetAt = $balance->last_reset_at ? Carbon::parse($balance->last_reset_at) : null;

            // Yearly rollover: carry unused annual days into the new year once.
            if (!$lastResetAt || $lastResetAt->lt($currentYearStart)) {
                $carryForward = max((float) ($balance->balance_days ?? 0), 0);
                $baseEntitlement = $this->resolveEntitlementDays($employee, $leaveType);
                $newEntitlement = $baseEntitlement + $carryForward;

                $balance->carry_forward_days = $carryForward;
                $balance->entitled_days = $newEntitlement;
                $balance->used_days = 0;
                $balance->balance_days = $newEntitlement;
                $balance->last_reset_at = $currentYearStart->toDateString();
                $balance->save();
            }
        }

        return $balance;
    }

    protected function resolveEntitlementDays(Employee $employee, LeaveTypeSetting $leaveType): float
    {
        $leaveTypeKey = strtolower((string) $leaveType->type);
        if ($leaveTypeKey === 'annual') {
            return (float) ($employee->annual_leave_days ?? 21);
        }

        if (!is_null($leaveType->days_per_year)) {
            return (float) $leaveType->days_per_year;
        }

        return (float) (($leaveType->full_pay_days ?? 0) + ($leaveType->half_pay_days ?? 0));
    }

    protected function deductLeaveBalance(LeaveRequest $leave): void
    {
        $employee = Employee::find($leave->employee_id);
        if (!$employee) {
            return;
        }

        $leaveType = LeaveTypeSetting::where('company_id', $employee->company_id)
            ->where('type', strtolower((string) $leave->type))
            ->first();

        if (!$leaveType) {
            return;
        }

        $balance = $this->getOrCreateEmployeeLeaveBalance($employee, $leaveType);
        $days = (float) ($leave->leave_days ?? 0);

        $balance->used_days = min(
            (float) $balance->used_days + $days,
            (float) $balance->entitled_days
        );
        $balance->balance_days = max((float) $balance->entitled_days - (float) $balance->used_days, 0);
        $balance->save();

        if (strtolower((string) $leave->type) === 'annual') {
            $this->syncAnnualColumnsToEmployee($employee, $balance);
        }
    }

    protected function syncAnnualColumnsToEmployee(Employee $employee, EmployeeLeaveBalance $balance): void
    {
        $baseAnnualDays = (float) $balance->entitled_days - (float) ($balance->carry_forward_days ?? 0);
        $employee->annual_leave_days = (int) round(max($baseAnnualDays, 0));
        $employee->annual_leave_used = (int) round((float) $balance->used_days);
        $employee->annual_leave_balance = (int) round((float) $balance->balance_days);
        $employee->save();
    }

    protected function notifyApproversOnNewRequest(
        LeaveRequest $leave,
        Employee $employee,
        LeaveTypeSetting $leaveType,
        ?int $companyId
    ): void {
        $approvers = $this->resolveApprovers($employee, $companyId);
        if (empty($approvers)) {
            return;
        }

        $title = 'New Leave Request';
        $message = "{$employee->name} requested {$leave->leave_days} day(s) of {$leaveType->label}.";
        $data = [
            'leave_request_id' => $leave->id,
            'employee_id' => $employee->id,
            'type' => $leave->type,
            'status' => $leave->status,
        ];

        $this->notificationService->notifyUsers($approvers, $title, $message, 'leave_request', $data, $companyId);
        $this->sendEmailToUsers($approvers, $title, $message);
    }

    protected function notifyEmployeeOnDecision(LeaveRequest $leave, bool $approved): void
    {
        $employee = Employee::with('user')->find($leave->employee_id);
        if (!$employee || !$employee->user_id) {
            return;
        }

        $title = $approved ? 'Leave Approved' : 'Leave Rejected';
        $message = $approved
            ? "Your {$leave->type} leave request was approved."
            : "Your {$leave->type} leave request was rejected.";
        $data = [
            'leave_request_id' => $leave->id,
            'type' => $leave->type,
            'status' => $leave->status,
        ];

        $this->notificationService->notifyUser(
            $employee->user_id,
            $title,
            $message,
            'leave_decision',
            $data,
            $employee->company_id
        );

        $this->sendEmailToUsers([$employee->user_id], $title, $message);
    }

    protected function resolveApprovers(Employee $employee, ?int $companyId): array
    {
        $approverIds = [];

        $hierarchyManagerIds = EmployeeManagerAssignment::where('employee_id', $employee->id)
            ->whereIn('relationship_type', ['functional', 'dotted'])
            ->where(function ($query) {
                $query->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString());
            })
            ->pluck('manager_user_id')
            ->all();
        $approverIds = array_merge($approverIds, array_map('intval', $hierarchyManagerIds));

        if ($employee->department_id) {
            $managerId = Department::where('id', $employee->department_id)->value('manager_id');
            if ($managerId) {
                $approverIds[] = (int) $managerId;
            }
        }

        $roleApprovers = User::where('company_id', $companyId)
            ->whereHas('role', function ($query) {
                $query->whereIn('title', ['HR', 'Director', 'MD']);
            })
            ->pluck('id')
            ->all();

        return array_values(array_unique(array_merge($approverIds, array_map('intval', $roleApprovers))));
    }

    protected function sendEmailToUsers(array $userIds, string $subject, string $body): void
    {
        if (empty($userIds)) {
            return;
        }

        $emails = User::whereIn('id', $userIds)
            ->whereNotNull('email')
            ->pluck('email')
            ->unique()
            ->values()
            ->all();

        foreach ($emails as $email) {
            try {
                Mail::raw($body, function ($message) use ($email, $subject) {
                    $message->to($email)->subject($subject);
                });
            } catch (\Throwable $exception) {
                Log::warning('Failed to send leave notification email', [
                    'email' => $email,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }
}

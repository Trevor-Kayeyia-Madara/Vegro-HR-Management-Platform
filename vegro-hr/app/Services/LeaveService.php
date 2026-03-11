<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\Employee;
use Carbon\Carbon;

class LeaveService
{
    public function requestLeave(array $data)
    {
        $payload = $data;
        $payload['type'] = $payload['type'] ?? 'annual';
        $payload['status'] = 'pending';
        $payload['leave_days'] = $this->calculateLeaveDays(
            $payload['start_date'] ?? null,
            $payload['end_date'] ?? null
        );

        if (!isset($payload['reason'])) {
            $payload['reason'] = $payload['type'] === 'annual' ? 'Annual leave' : null;
        }

        if ($payload['type'] === 'annual') {
            $employeeId = $payload['employee_id'] ?? null;
            $employee = $employeeId ? Employee::find($employeeId) : null;
            if ($employee) {
                $balance = (int) $employee->annual_leave_balance;
                $requested = (int) $payload['leave_days'];
                if ($requested > $balance) {
                    throw new \RuntimeException('Requested days exceed annual leave balance.');
                }
            }
        }

        return LeaveRequest::create($payload);
    }

    public function approveLeave(LeaveRequest $leave, $userId)
    {
        $leave->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_role' => auth()->user()?->role?->title,
            'approved_at' => now(),
        ]);

        $this->applyAnnualLeaveBalance($leave);

        return $leave;
    }

    public function rejectLeave(LeaveRequest $leave, $userId)
    {
        $leave->update([
            'status' => 'rejected',
            'approved_by' => $userId,
            'approved_role' => auth()->user()?->role?->title,
            'approved_at' => now(),
        ]);

        return $leave;
    }

    public function getLeaveById($id)
    {
        return LeaveRequest::findOrFail($id);
    }

    public function getLeavesByEmployee($employeeId)
    {
        return LeaveRequest::with(['employee', 'approver'])
            ->where('employee_id', $employeeId)
            ->get();
    }

    public function getPendingLeaves()
    {
        return LeaveRequest::where('status', 'pending')->get();
    }

    public function getApprovedLeaves()
    {
        return LeaveRequest::where('status', 'approved')->get();
    }

    public function getRejectedLeaves()
    {
        return LeaveRequest::where('status', 'rejected')->get();
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
        return LeaveRequest::where('status', $status)->get();
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
        $departmentIds = \App\Models\Department::where('manager_id', $managerId)->pluck('id');
        if ($departmentIds->isEmpty()) {
            return LeaveRequest::whereRaw('1=0')->paginate($perPage);
        }

        return LeaveRequest::with(['employee', 'approver'])
            ->whereHas('employee', function ($query) use ($departmentIds) {
                $query->whereIn('department_id', $departmentIds);
            })
            ->paginate($perPage);
    }

    public function getLeavesForManagerByStatus($managerId, $status)
    {
        $departmentIds = \App\Models\Department::where('manager_id', $managerId)->pluck('id');
        if ($departmentIds->isEmpty()) {
            return collect([]);
        }

        return LeaveRequest::with(['employee', 'approver'])
            ->where('status', $status)
            ->whereHas('employee', function ($query) use ($departmentIds) {
                $query->whereIn('department_id', $departmentIds);
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
        $leaves = LeaveRequest::all();
        $csvData = "ID,Employee ID,Type,Start Date,End Date,Days,Status,Approved By,Approved Role\n";

        foreach ($leaves as $leave) {
            $csvData .= "{$leave->id},{$leave->employee_id},{$leave->type},{$leave->start_date},{$leave->end_date},{$leave->leave_days},{$leave->status},{$leave->approved_by},{$leave->approved_role}\n";
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
        return LeaveRequest::where('status', $status)->get();
    }

    protected function calculateLeaveDays($startDate, $endDate): int
    {
        if (!$startDate || !$endDate) {
            return 0;
        }
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();
        if ($end->lessThan($start)) {
            return 0;
        }
        return $start->diffInDays($end) + 1;
    }

    protected function applyAnnualLeaveBalance(LeaveRequest $leave): void
    {
        if ($leave->type !== 'annual') {
            return;
        }

        $employee = Employee::find($leave->employee_id);
        if (!$employee) {
            return;
        }

        $days = (int) ($leave->leave_days ?? 0);
        $employee->annual_leave_used = min(
            (int) $employee->annual_leave_used + $days,
            (int) $employee->annual_leave_days
        );
        $employee->annual_leave_balance = max(
            (int) $employee->annual_leave_days - (int) $employee->annual_leave_used,
            0
        );
        $employee->save();
    }
}

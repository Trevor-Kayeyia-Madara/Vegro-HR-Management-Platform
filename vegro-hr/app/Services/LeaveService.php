<?php

namespace App\Services;

use App\Models\LeaveRequest;

class LeaveService
{
    public function requestLeave(array $data)
    {
        return LeaveRequest::create($data);
    }

    public function approveLeave(LeaveRequest $leave, $userId)
    {
        $leave->update([
            'status' => 'approved',
            'approved_by' => $userId
        ]);

        return $leave;
    }

    public function getAllLeaveRequests()
    {
        return LeaveRequest::with(['employee','approver'])->get();
    }   
    public function rejectLeave(LeaveRequest $leave, $userId)
    {
        $leave->update([
            'status' => 'rejected',
            'approved_by' => $userId
        ]);

        return $leave;
    }
    public function deleteLeave(LeaveRequest $leave)
    {
            $leave->delete();
            return true;
    }

    public function getLeaveRequestsByEmployee($employeeId)
    {
        return LeaveRequest::where('employee_id', $employeeId)->get();
    }

    public function updateLeave(LeaveRequest $leave, array $data)
    {
        $leave->update($data);
        return $leave;
    }

}
<?php

namespace App\Repositories;
use App\Models\LeaveRequest;

class LeaveRepository
{
    public function getAllLeaveRequests()
    {
        return LeaveRequest::all();
    }

    public function getLeaveRequestById($id)
    {
        return LeaveRequest::find($id);
    }

    public function createLeaveRequest($data)
    {
        return LeaveRequest::create($data);
    }

    public function updateLeaveRequest($id, $data)
    {
        $leaveRequest = LeaveRequest::find($id);
        if ($leaveRequest) {
            $leaveRequest->update($data);
            return $leaveRequest;
        }
        return null;
    }

    public function deleteLeaveRequest($id)
    {
        $leaveRequest = LeaveRequest::find($id);
        if ($leaveRequest) {
            $leaveRequest->delete();
            return true;
        }
        return false;
    }

    public function getLeaveRequestsByEmployeeId($employeeId)
    {
        return LeaveRequest::where('employee_id', $employeeId)->get();
    }

    public function getLeaveRequestsByStatus($status)
    {
        return LeaveRequest::where('status', $status)->get();
    }
}
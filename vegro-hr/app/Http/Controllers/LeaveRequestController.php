<?php

namespace App\Http\Controllers;

use App\Services\LeaveService;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
class LeaveRequestController extends Controller
{
    protected $leaveService;

    public function __construct(LeaveService $leaveService)
    {
        $this->leaveService = $leaveService;
    }

    public function index()
    {
        return ApiResponse::success($this->leaveService->getAllLeaveRequests());
    }

    public function store(Request $request)
    {
        return ApiResponse::success($this->leaveService->requestLeave($request->all()), "Leave request submitted successfully", 201);
    }

    public function approve($id)
    {
        $leave = $this->leaveService->getLeaveById($id);
        return ApiResponse::success($this->leaveService->approveLeave($leave, auth()->id()));
    }

    public function reject($id)
    {
        $leave = $this->leaveService->getLeaveById($id);
        return ApiResponse::success($this->leaveService->rejectLeave($leave, auth()->id()));
    }

    public function destroy($id)
    {
        $leave = $this->leaveService->getLeaveById($id);
        return $this->leaveService->deleteLeave($leave);
    }

    public function show($id)
    {
        return $this->leaveService->getLeaveById($id);
    }

    public function getLeavesByEmployee($employeeId)
    {
        return $this->leaveService->getLeavesByEmployee($employeeId);
    }

    public function getPendingLeaves()
    {
        return $this->leaveService->getPendingLeaves();
    }

    public function getApprovedLeaves()
    {
        return $this->leaveService->getApprovedLeaves();
    }

    public function getRejectedLeaves()
    {
        return $this->leaveService->getRejectedLeaves();
    }
    
    public function getAllLeaveRequests()
    {
        return ApiResponse::success($this->leaveService->getAllLeaveRequests());
    }

    public function getLeaveRequestsByStatus($status)
    {
        return ApiResponse::success($this->leaveService->getLeaveRequestsByStatus($status));
    }

    public function exportLeavesToCSV()
    {
        return ApiResponse::success($this->leaveService->exportLeavesToCSV());
    }
}
    
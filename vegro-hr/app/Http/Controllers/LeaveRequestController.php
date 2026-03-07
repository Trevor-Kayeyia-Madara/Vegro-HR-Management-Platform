<?php

namespace App\Http\Controllers;

use App\Services\LeaveService;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    protected $leaveService;
    public function __construct(LeaveService $leaveService)
    {
        $this->leaveService = $leaveService;
    }
    
    public function index()
    {
        return $this->leaveService->getAllLeaveRequests();
    }

    public function destroy($leave)
    {
        return $this->leaveService->deleteLeave($leave);
    }

    public function approve($leave, Request $request)
    {
        $userId = $request->user()->id;
        return $this->leaveService->approveLeave($leave, $userId);
    }

    public function reject($leave, Request $request)
    {
        $userId = $request->user()->id;
        return $this->leaveService->rejectLeave($leave, $userId);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
        ]);
    
        return $this->leaveService->requestLeave($validated);
        
    }

    public function getByEmployee($employeeId)
    {
        return $this->leaveService->getLeaveRequestsByEmployee($employeeId);
    }

    public function update(Request $request, $leave)
    {
        $validated = $request->validate([
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            'reason' => 'sometimes|nullable|string',
        ]);

        return $this->leaveService->updateLeave($leave, $validated);
    }

}

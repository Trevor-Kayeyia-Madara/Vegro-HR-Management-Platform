<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    public function index()
    {
        return LeaveRequest::with(['employee','approver'])->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'type' => 'required|in:annual,sick,emergency',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'nullable|in:pending,approved,rejected',
            'approved_by' => 'nullable|exists:users,id'
        ]);

        return LeaveRequest::create($validated);
    }

    public function show(LeaveRequest $leaveRequest)
    {
        return $leaveRequest->load(['employee','approver']);
    }

    public function update(Request $request, LeaveRequest $leaveRequest)
    {
        $validated = $request->validate([
            'type' => 'nullable|in:annual,sick,emergency',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:pending,approved,rejected',
            'approved_by' => 'nullable|exists:users,id'
        ]);

        $leaveRequest->update($validated);
        return $leaveRequest;
    }

    public function destroy(LeaveRequest $leaveRequest)
    {
        $leaveRequest->delete();
        return response()->noContent();
    }
}
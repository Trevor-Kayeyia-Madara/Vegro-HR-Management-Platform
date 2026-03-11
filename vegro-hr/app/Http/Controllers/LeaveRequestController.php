<?php

namespace App\Http\Controllers;

use App\Services\LeaveService;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use OpenApi\Attributes as OA;

class LeaveRequestController extends Controller
{
    protected $leaveService;

    public function __construct(LeaveService $leaveService)
    {
        $this->leaveService = $leaveService;
    }

    protected function userCanApprove($leave): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        if ($user->hasRole(['admin', 'hr', 'director', 'md'])) {
            return true;
        }

        if ($user->hasRole('manager')) {
            $departmentId = $leave?->employee?->department_id;
            if (!$departmentId) {
                return false;
            }
            return \App\Models\Department::where('id', $departmentId)
                ->where('manager_id', $user->id)
                ->exists();
        }

        return false;
    }

    protected function resolveEmployeeForUser($user)
    {
        if (!$user) {
            return null;
        }

        return \App\Models\Employee::where('user_id', $user->id)->first();
    }

    #[OA\Get(
        path: "/api/leave-requests",
        operationId: "getLeaveRequests",
        description: "Get list of all leave requests",
        summary: "List all leave requests",
        tags: ["Leave Requests"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Leave requests retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: ""),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer"),
                                    new OA\Property(property: "employee_id", type: "integer"),
                                    new OA\Property(property: "start_date", type: "string", format: "date"),
                                    new OA\Property(property: "end_date", type: "string", format: "date"),
                                    new OA\Property(property: "reason", type: "string"),
                                    new OA\Property(property: "status", type: "string", enum: ["pending", "approved", "rejected"]),
                                    new OA\Property(property: "created_at", type: "string", format: "date-time"),
                                    new OA\Property(property: "updated_at", type: "string", format: "date-time")
                                ]
                            )
                        )
                    ]
                )
            )
        ]
    )]
    public function index()
    {
        $perPage = max((int) request()->query('per_page', 10), 1);
        $user = auth()->user();

        if ($user && $user->hasRole('employee')) {
            $employee = $this->resolveEmployeeForUser($user);
            if (!$employee) {
                return ApiResponse::success(collect([]));
            }
            return ApiResponse::success($this->leaveService->getLeavesByEmployee($employee->id));
        }

        if ($user && $user->hasRole('manager')) {
            return ApiResponse::success($this->leaveService->getLeavesForManagerPaginated($user->id, $perPage));
        }

        return ApiResponse::success($this->leaveService->getAllLeaveRequestsPaginated($perPage));
    }

    #[OA\Post(
        path: "/api/leave-requests",
        operationId: "storeLeaveRequest",
        description: "Create a new leave request",
        summary: "Create leave request",
        tags: ["Leave Requests"],
        requestBody: new OA\RequestBody(
            description: "Leave request data",
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["employee_id", "start_date", "end_date", "status"],
                properties: [
                    new OA\Property(property: "employee_id", type: "integer", description: "Employee ID", example: 1),
                    new OA\Property(property: "start_date", type: "string", format: "date", description: "Leave start date", example: "2026-03-15"),
                    new OA\Property(property: "end_date", type: "string", format: "date", description: "Leave end date", example: "2026-03-20"),
                    new OA\Property(property: "reason", type: "string", description: "Reason for leave", example: "Vacation", nullable: true),
                    new OA\Property(property: "status", type: "string", description: "Leave status", enum: ["pending", "approved", "rejected"], example: "pending")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Leave request created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Leave request submitted successfully"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(Request $request)
    {
        $user = auth()->user();
        $payload = $request->all();

        if ($user && $user->hasRole('employee')) {
            $employee = $this->resolveEmployeeForUser($user);
            if (!$employee) {
                return ApiResponse::error('Employee profile not found', 404);
            }
            $payload['employee_id'] = $employee->id;
        }

        try {
            return ApiResponse::success(
                $this->leaveService->requestLeave($payload),
                "Leave request submitted successfully",
                201
            );
        } catch (\RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }
    }

    public function getApprovalChain()
    {
        $user = auth()->user();
        $employee = $this->resolveEmployeeForUser($user);
        $manager = null;

        if ($employee?->department_id) {
            $department = \App\Models\Department::with('manager')->find($employee->department_id);
            $manager = $department?->manager;
        }

        $hrUsers = \App\Models\User::whereHas('role', function ($query) {
            $query->where('title', 'HR');
        })->get(['id', 'name', 'email']);

        $directors = \App\Models\User::whereHas('role', function ($query) {
            $query->whereIn('title', ['Director', 'MD']);
        })->with('role')->get(['id', 'name', 'email', 'role_id']);

        return ApiResponse::success([
            'manager' => $manager ? [
                'id' => $manager->id,
                'name' => $manager->name,
                'email' => $manager->email,
            ] : null,
            'hr' => $hrUsers,
            'directors' => $directors->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role?->title,
                ];
            }),
        ]);
    }

    #[OA\Get(
        path: "/api/leave-requests/{id}",
        operationId: "getLeaveRequest",
        description: "Get a specific leave request",
        summary: "Get leave request by ID",
        tags: ["Leave Requests"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Leave Request ID",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Leave request retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: ""),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Leave request not found")
        ]
    )]
    public function show($id)
    {
        $leave = $this->leaveService->getLeaveById($id);
        $user = auth()->user();

        if ($user && $user->hasRole('employee')) {
            $employee = $this->resolveEmployeeForUser($user);
            if (!$employee || $leave->employee_id !== $employee->id) {
                return ApiResponse::forbidden('You can only view your own leave requests');
            }
        }

        return $leave;
    }

    #[OA\Post(
        path: "/api/leave-requests/{id}/approve",
        operationId: "approveLeaveRequest",
        description: "Approve a leave request",
        summary: "Approve leave request",
        tags: ["Leave Requests"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Leave Request ID",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Leave request approved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: ""),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Leave request not found")
        ]
    )]
    public function approve($id)
    {
        $leave = $this->leaveService->getLeaveById($id);
        if (!$this->userCanApprove($leave)) {
            return ApiResponse::forbidden('You are not allowed to approve this leave request');
        }
        if ($leave->status !== 'pending') {
            return ApiResponse::success($leave);
        }
        return ApiResponse::success($this->leaveService->approveLeave($leave, auth()->id()));
    }

    #[OA\Post(
        path: "/api/leave-requests/{id}/reject",
        operationId: "rejectLeaveRequest",
        description: "Reject a leave request",
        summary: "Reject leave request",
        tags: ["Leave Requests"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Leave Request ID",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Leave request rejected successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: ""),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Leave request not found")
        ]
    )]
    public function reject($id)
    {
        $leave = $this->leaveService->getLeaveById($id);
        if (!$this->userCanApprove($leave)) {
            return ApiResponse::forbidden('You are not allowed to reject this leave request');
        }
        if ($leave->status !== 'pending') {
            return ApiResponse::success($leave);
        }
        return ApiResponse::success($this->leaveService->rejectLeave($leave, auth()->id()));
    }

    #[OA\Delete(
        path: "/api/leave-requests/{id}",
        operationId: "deleteLeaveRequest",
        description: "Delete a leave request",
        summary: "Delete leave request",
        tags: ["Leave Requests"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Leave Request ID",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Leave request deleted successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: ""),
                        new OA\Property(property: "data", type: "null")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Leave request not found")
        ]
    )]
    public function destroy($id)
    {
        $leave = $this->leaveService->getLeaveById($id);
        $user = auth()->user();
        if ($user && $user->hasRole('employee')) {
            $employee = $this->resolveEmployeeForUser($user);
            if (!$employee || $leave->employee_id !== $employee->id) {
                return ApiResponse::forbidden('You can only delete your own leave requests');
            }
        }
        return $this->leaveService->deleteLeave($leave);
    }

    #[OA\Get(
        path: "/api/leave-requests/employee/{employeeId}",
        operationId: "getLeavesByEmployee",
        description: "Get leave requests by employee",
        summary: "Get leaves by employee",
        tags: ["Leave Requests"],
        parameters: [
            new OA\Parameter(
                name: "employeeId",
                in: "path",
                required: true,
                description: "Employee ID",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Leave requests retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: ""),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object"))
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Employee not found")
        ]
    )]
    public function getLeavesByEmployee($employeeId)
    {
        $user = auth()->user();
        if ($user && $user->hasRole('employee')) {
            $employee = $this->resolveEmployeeForUser($user);
            if (!$employee || (int) $employeeId !== (int) $employee->id) {
                return ApiResponse::forbidden('You can only view your own leave requests');
            }
        }
        return $this->leaveService->getLeavesByEmployee($employeeId);
    }

    #[OA\Get(
        path: "/api/leave-requests/status/pending",
        operationId: "getPendingLeaves",
        description: "Get pending leave requests",
        summary: "Get pending leaves",
        tags: ["Leave Requests"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Pending leave requests retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: ""),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object"))
                    ]
                )
            )
        ]
    )]
    public function getPendingLeaves()
    {
        if (!auth()->user()?->hasRole(['admin', 'hr', 'manager', 'director', 'md'])) {
            return ApiResponse::forbidden('Forbidden');
        }
        $user = auth()->user();
        if ($user && $user->hasRole('manager')) {
            return $this->leaveService->getLeavesForManagerByStatus($user->id, 'pending');
        }
        return $this->leaveService->getPendingLeaves();
    }

    #[OA\Get(
        path: "/api/leave-requests/status/approved",
        operationId: "getApprovedLeaves",
        description: "Get approved leave requests",
        summary: "Get approved leaves",
        tags: ["Leave Requests"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Approved leave requests retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: ""),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object"))
                    ]
                )
            )
        ]
    )]
    public function getApprovedLeaves()
    {
        if (!auth()->user()?->hasRole(['admin', 'hr', 'manager', 'director', 'md'])) {
            return ApiResponse::forbidden('Forbidden');
        }
        $user = auth()->user();
        if ($user && $user->hasRole('manager')) {
            return $this->leaveService->getLeavesForManagerByStatus($user->id, 'approved');
        }
        return $this->leaveService->getApprovedLeaves();
    }

    #[OA\Get(
        path: "/api/leave-requests/status/rejected",
        operationId: "getRejectedLeaves",
        description: "Get rejected leave requests",
        summary: "Get rejected leaves",
        tags: ["Leave Requests"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Rejected leave requests retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: ""),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object"))
                    ]
                )
            )
        ]
    )]
    public function getRejectedLeaves()
    {
        if (!auth()->user()?->hasRole(['admin', 'hr', 'manager', 'director', 'md'])) {
            return ApiResponse::forbidden('Forbidden');
        }
        $user = auth()->user();
        if ($user && $user->hasRole('manager')) {
            return $this->leaveService->getLeavesForManagerByStatus($user->id, 'rejected');
        }
        return $this->leaveService->getRejectedLeaves();
    }

    #[OA\Get(
        path: "/api/leave-requests/all",
        operationId: "getAllLeaveRequests",
        description: "Get all leave requests",
        summary: "Get all leave requests",
        tags: ["Leave Requests"],
        responses: [
            new OA\Response(
                response: 200,
                description: "All leave requests retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: ""),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object"))
                    ]
                )
            )
        ]
    )]
    public function getAllLeaveRequests()
    {
        if (!auth()->user()?->hasRole(['admin', 'hr', 'manager', 'director', 'md'])) {
            return ApiResponse::forbidden('Forbidden');
        }
        $perPage = max((int) request()->query('per_page', 10), 1);
        $user = auth()->user();
        if ($user && $user->hasRole('manager')) {
            return ApiResponse::success($this->leaveService->getLeavesForManagerPaginated($user->id, $perPage));
        }
        return ApiResponse::success($this->leaveService->getAllLeaveRequestsPaginated($perPage));
    }

    #[OA\Get(
        path: "/api/leave-requests/status/{status}",
        operationId: "getLeaveRequestsByStatus",
        description: "Get leave requests by status",
        summary: "Get leaves by status",
        tags: ["Leave Requests"],
        parameters: [
            new OA\Parameter(
                name: "status",
                in: "path",
                required: true,
                description: "Leave status",
                schema: new OA\Schema(type: "string", enum: ["pending", "approved", "rejected"])
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Leave requests retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: ""),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object"))
                    ]
                )
            )
        ]
    )]
    public function getLeaveRequestsByStatus($status)
    {
        if (!auth()->user()?->hasRole(['admin', 'hr', 'manager', 'director', 'md'])) {
            return ApiResponse::forbidden('Forbidden');
        }
        return ApiResponse::success($this->leaveService->getLeaveRequestsByStatus($status));
    }

    #[OA\Get(
        path: "/api/leave-requests/export/csv",
        operationId: "exportLeavesToCSV",
        description: "Export leave requests to CSV",
        summary: "Export leaves to CSV",
        tags: ["Leave Requests"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Leave requests exported successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: ""),
                        new OA\Property(property: "data", type: "string")
                    ]
                )
            )
        ]
    )]
    public function exportLeavesToCSV()
    {
        if (!auth()->user()?->hasRole(['admin', 'hr', 'manager', 'director', 'md'])) {
            return ApiResponse::forbidden('Forbidden');
        }
        return ApiResponse::success($this->leaveService->exportLeavesToCSV());
    }
}

    

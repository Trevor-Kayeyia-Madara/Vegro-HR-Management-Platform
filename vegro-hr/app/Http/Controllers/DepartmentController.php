<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\EmployeeManagerAssignment;
use App\Models\ProjectMembership;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class DepartmentController extends Controller
{
    #[OA\Get(
        path: "/api/departments",
        operationId: "listDepartments",
        description: "Get all departments",
        summary: "List all departments",
        tags: ["Departments"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Departments retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Departments retrieved successfully"),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "name", type: "string", example: "Sales"),
                                    new OA\Property(property: "created_at", type: "string", format: "date-time"),
                                    new OA\Property(property: "updated_at", type: "string", format: "date-time")
                                ]
                            )
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function index()
    {
        $perPage = max((int) request()->query('per_page', 10), 1);
        $departments = Department::with(['manager:id,name,email'])
            ->withCount('employees')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return ApiResponse::success($departments, "Departments retrieved successfully");
    }

    #[OA\Post(
        path: "/api/departments",
        operationId: "createDepartment",
        description: "Create a new department",
        summary: "Create department",
        tags: ["Departments"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            description: "Department data",
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["name"],
                properties: [
                    new OA\Property(property: "name", type: "string", description: "Department name", example: "Sales")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Department created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "name", type: "string", example: "Sales"),
                        new OA\Property(property: "created_at", type: "string", format: "date-time"),
                        new OA\Property(property: "updated_at", type: "string", format: "date-time")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation error"),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function store(Request $request)
    {
        $companyId = $request->attributes->get('company_id') ?? auth()->user()?->company_id;
        $validated = $request->validate([
            'name' => [
                'required',
                Rule::unique('departments', 'name')->where('company_id', $companyId),
            ],
            'description' => 'nullable|string',
            'manager_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('company_id', $companyId),
            ],
        ]);

        $department = Department::create($validated);
        $department->load('manager:id,name,email');
        $department->loadCount('employees');

        return ApiResponse::success($department, "Department created successfully", 201);
    }

    #[OA\Get(
        path: "/api/departments/{department}",
        operationId: "showDepartment",
        description: "Get a specific department",
        summary: "Get department by ID",
        tags: ["Departments"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "department",
                description: "Department ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Department retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "name", type: "string", example: "Sales"),
                        new OA\Property(property: "created_at", type: "string", format: "date-time"),
                        new OA\Property(property: "updated_at", type: "string", format: "date-time")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Department not found"),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function show(Department $department)
    {
        $department->load('manager:id,name,email');
        $department->loadCount('employees');
        return ApiResponse::success($department, "Department retrieved successfully");
    }

    #[OA\Put(
        path: "/api/departments/{department}",
        operationId: "updateDepartment",
        description: "Update a department",
        summary: "Update department",
        tags: ["Departments"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "department",
                description: "Department ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            description: "Department data",
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["name"],
                properties: [
                    new OA\Property(property: "name", type: "string", description: "Department name", example: "Sales Updated")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Department updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "name", type: "string", example: "Sales Updated"),
                        new OA\Property(property: "created_at", type: "string", format: "date-time"),
                        new OA\Property(property: "updated_at", type: "string", format: "date-time")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Department not found"),
            new OA\Response(response: 422, description: "Validation error"),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function update(Request $request, Department $department)
    {
        $companyId = $request->attributes->get('company_id') ?? auth()->user()?->company_id;
        $validated = $request->validate([
            'name' => [
                'required',
                Rule::unique('departments', 'name')
                    ->where('company_id', $companyId)
                    ->ignore($department->id),
            ],
            'description' => 'nullable|string',
            'manager_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('company_id', $companyId),
            ],
        ]);
        $department->update($validated);
        $department->load('manager:id,name,email');
        $department->loadCount('employees');
        return ApiResponse::success($department, "Department updated successfully");
    }

    #[OA\Delete(
        path: "/api/departments/{department}",
        operationId: "deleteDepartment",
        description: "Delete a department",
        summary: "Delete department",
        tags: ["Departments"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "department",
                description: "Department ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(response: 204, description: "Department deleted successfully"),
            new OA\Response(response: 404, description: "Department not found"),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function destroy(Department $department)
    {
        $department->delete();
        return response()->noContent();
    }

    public function orgChart()
    {
        $departments = Department::with([
            'manager:id,name,email',
            'employees:id,department_id,user_id,name,position',
        ])
            ->withCount('employees')
            ->orderBy('name')
            ->get();

        return ApiResponse::success($departments, 'Org chart retrieved');
    }

    public function matrixOrgChart(Request $request)
    {
        $user = $request->user();

        if ($user && $user->hasRole('employee')) {
            $employee = \App\Models\Employee::with(['department:id,name,manager_id', 'department.manager:id,name,email'])
                ->where('user_id', $user->id)
                ->first();

            if (!$employee) {
                return ApiResponse::success(['employee' => null], 'Org chart retrieved');
            }

            $managerAssignments = EmployeeManagerAssignment::with('manager:id,name,email')
                ->where('employee_id', $employee->id)
                ->get();

            $projectMemberships = ProjectMembership::with([
                'project:id,name,status',
                'reportsTo:id,name,email',
            ])
                ->where('employee_id', $employee->id)
                ->get();

            return ApiResponse::success([
                'employee' => $employee,
                'reporting_lines' => $managerAssignments,
                'projects' => $projectMemberships,
            ], 'Org chart retrieved');
        }

        $departments = Department::with(['manager:id,name,email', 'employees:id,department_id,user_id,name,email,position'])
            ->withCount('employees')
            ->orderBy('name')
            ->get();

        $employeeIds = $departments
            ->flatMap(fn ($dept) => $dept->employees->pluck('id'))
            ->values()
            ->all();

        $managerAssignments = EmployeeManagerAssignment::with('manager:id,name,email')
            ->whereIn('employee_id', $employeeIds)
            ->get()
            ->groupBy('employee_id');

        $projectMemberships = ProjectMembership::with([
            'project:id,name,status',
            'reportsTo:id,name,email',
        ])
            ->whereIn('employee_id', $employeeIds)
            ->get()
            ->groupBy('employee_id');

        $payload = $departments->map(function ($department) use ($managerAssignments, $projectMemberships) {
            $employees = $department->employees->map(function ($employee) use ($managerAssignments, $projectMemberships) {
                $lines = $managerAssignments->get($employee->id, collect());
                $projects = $projectMemberships->get($employee->id, collect());

                return [
                    'id' => $employee->id,
                    'user_id' => $employee->user_id,
                    'name' => $employee->name,
                    'email' => $employee->email,
                    'position' => $employee->position,
                    'department_id' => $employee->department_id,
                    'reporting_lines' => $lines->map(function ($line) {
                        return [
                            'id' => $line->id,
                            'relationship_type' => $line->relationship_type,
                            'manager' => $line->manager ? [
                                'id' => $line->manager->id,
                                'name' => $line->manager->name,
                                'email' => $line->manager->email,
                            ] : null,
                        ];
                    })->values(),
                    'projects' => $projects->map(function ($membership) {
                        return [
                            'id' => $membership->id,
                            'project' => $membership->project ? [
                                'id' => $membership->project->id,
                                'name' => $membership->project->name,
                                'status' => $membership->project->status,
                            ] : null,
                            'role_title' => $membership->role_title,
                            'allocation_percent' => $membership->allocation_percent,
                            'reports_to' => $membership->reportsTo ? [
                                'id' => $membership->reportsTo->id,
                                'name' => $membership->reportsTo->name,
                                'email' => $membership->reportsTo->email,
                            ] : null,
                        ];
                    })->values(),
                ];
            });

            return [
                'id' => $department->id,
                'name' => $department->name,
                'description' => $department->description,
                'employees_count' => $department->employees_count ?? $department->employees->count(),
                'manager' => $department->manager ? [
                    'id' => $department->manager->id,
                    'name' => $department->manager->name,
                    'email' => $department->manager->email,
                ] : null,
                'employees' => $employees,
            ];
        });

        return ApiResponse::success($payload, 'Matrix org chart retrieved');
    }

    public function getOrgChartLayout(Request $request)
    {
        $companyId = $request->attributes->get('company_id') ?? $request->user()?->company_id;
        if (!$companyId) {
            return ApiResponse::success([
                'layout' => [],
                'top_user_id' => null,
            ], 'Org chart layout retrieved');
        }

        $settings = CompanySetting::firstOrCreate(['company_id' => $companyId]);
        $branding = is_array($settings->branding) ? $settings->branding : [];

        return ApiResponse::success([
            'layout' => data_get($branding, 'org_chart_layout', []),
            'node_meta' => data_get($branding, 'org_chart_node_meta', []),
            'top_user_id' => data_get($branding, 'org_chart_top_user_id'),
            'top_title' => data_get($branding, 'org_chart_top_title', 'CEO'),
        ], 'Org chart layout retrieved');
    }

    public function saveOrgChartLayout(Request $request)
    {
        $companyId = $request->attributes->get('company_id') ?? $request->user()?->company_id;
        if (!$companyId) {
            return ApiResponse::error('Company context missing', 422);
        }

        $validated = $request->validate([
            'layout' => 'required|array',
            'layout.*.x' => 'required|numeric',
            'layout.*.y' => 'required|numeric',
            'node_meta' => 'nullable|array',
            'node_meta.*.width' => 'nullable|numeric|min:80|max:520',
            'node_meta.*.height' => 'nullable|numeric|min:50|max:320',
            'node_meta.*.line1' => 'nullable|string|max:120',
            'node_meta.*.line2' => 'nullable|string|max:120',
            'node_meta.*.line3' => 'nullable|string|max:120',
            'top_title' => 'nullable|string|max:60',
            'top_user_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('company_id', $companyId),
            ],
        ]);

        $settings = CompanySetting::firstOrCreate(['company_id' => $companyId]);
        $branding = is_array($settings->branding) ? $settings->branding : [];
        $branding['org_chart_layout'] = $validated['layout'] ?? [];
        $branding['org_chart_node_meta'] = $validated['node_meta'] ?? [];
        $branding['org_chart_top_user_id'] = $validated['top_user_id'] ?? null;
        $branding['org_chart_top_title'] = trim((string) ($validated['top_title'] ?? '')) ?: 'CEO';

        $settings->update(['branding' => $branding]);

        return ApiResponse::success([
            'layout' => $branding['org_chart_layout'],
            'node_meta' => $branding['org_chart_node_meta'],
            'top_user_id' => $branding['org_chart_top_user_id'],
            'top_title' => $branding['org_chart_top_title'],
        ], 'Org chart layout saved');
    }
}

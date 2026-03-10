<?php

namespace App\Http\Controllers;
use App\Services\EmployeeService;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use App\Http\Resources\EmployeeResource;
use OpenApi\Attributes as OA;

class EmployeeController extends Controller
{
    protected $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }

    #[OA\Post(
        path: "/api/employees",
        operationId: "storeEmployee",
        description: "Create a new employee",
        summary: "Create employee",
        tags: ["Employees"],
        requestBody: new OA\RequestBody(
            description: "Employee data",
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["first_name", "last_name", "email", "department_id", "role_id", "salary"],
                properties: [
                    new OA\Property(property: "first_name", type: "string", description: "Employee first name", example: "John"),
                    new OA\Property(property: "last_name", type: "string", description: "Employee last name", example: "Doe"),
                    new OA\Property(property: "email", type: "string", format: "email", description: "Employee email", example: "john.doe@example.com"),
                    new OA\Property(property: "phone", type: "string", description: "Employee phone", example: "+1234567890", nullable: true),
                    new OA\Property(property: "department_id", type: "integer", description: "Department ID", example: 1),
                    new OA\Property(property: "role_id", type: "integer", description: "Role ID", example: 1),
                    new OA\Property(property: "salary", type: "number", format: "float", description: "Employee salary", example: 50000.00)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Employee created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Employee created successfully"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(Request $request)
    {
        return ApiResponse::success($this->employeeService->createEmployee($request->all()), "Employee created successfully", 201);
    }

    #[OA\Get(
        path: "/api/employees",
        operationId: "getEmployees",
        description: "Get list of all employees",
        summary: "List all employees",
        tags: ["Employees"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Employees retrieved successfully",
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
                                    new OA\Property(property: "employee_number", type: "string"),
                                    new OA\Property(property: "name", type: "string"),
                                    new OA\Property(property: "email", type: "string", format: "email"),
                                    new OA\Property(property: "phone", type: "string"),
                                    new OA\Property(property: "department_id", type: "integer"),
                                    new OA\Property(property: "position", type: "string"),
                                    new OA\Property(property: "salary", type: "number", format: "float"),
                                    new OA\Property(property: "hire_date", type: "string", format: "date"),
                                    new OA\Property(property: "status", type: "string"),
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
            $employee = \App\Models\Employee::where('user_id', $user->id)->first();
            if (!$employee) {
                return ApiResponse::success(EmployeeResource::collection(collect([])));
            }
            return ApiResponse::success(EmployeeResource::collection(collect([$employee])));
        }

        $employees = $this->employeeService->getEmployeesPaginated($perPage);
        return ApiResponse::success(EmployeeResource::collection($employees));
    }

    #[OA\Get(
        path: "/api/employees/{id}",
        operationId: "getEmployee",
        description: "Get a specific employee",
        summary: "Get employee by ID",
        tags: ["Employees"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Employee ID",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Employee retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: ""),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Employee not found")
        ]
    )]
    public function show($id)
    {
        $employee = $this->employeeService->getEmployeeById($id);

        $user = auth()->user();
        if ($user && $user->hasRole('employee')) {
            if ($employee->user_id !== $user->id) {
                return ApiResponse::forbidden('You can only access your own profile');
            }
        }

        return ApiResponse::success(new EmployeeResource($employee));
    }

    #[OA\Put(
        path: "/api/employees/{id}",
        operationId: "updateEmployee",
        description: "Update an employee",
        summary: "Update employee",
        tags: ["Employees"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Employee ID",
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            description: "Employee data",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "first_name", type: "string", description: "Employee first name", example: "John", nullable: true),
                    new OA\Property(property: "last_name", type: "string", description: "Employee last name", example: "Doe", nullable: true),
                    new OA\Property(property: "email", type: "string", format: "email", description: "Employee email", example: "john.doe@example.com", nullable: true),
                    new OA\Property(property: "phone", type: "string", description: "Employee phone", example: "+1234567890", nullable: true),
                    new OA\Property(property: "department_id", type: "integer", description: "Department ID", example: 1, nullable: true),
                    new OA\Property(property: "role_id", type: "integer", description: "Role ID", example: 1, nullable: true),
                    new OA\Property(property: "salary", type: "number", format: "float", description: "Employee salary", example: 50000.00, nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Employee updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: ""),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Employee not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(Request $request, $id)
    {
        $employee = $this->employeeService->getEmployeeById($id);
        $user = auth()->user();

        if ($user && $user->hasRole('employee')) {
            if ($employee->user_id !== $user->id) {
                return ApiResponse::forbidden('You can only update your own profile');
            }
        }

        return ApiResponse::success($this->employeeService->updateEmployee($employee, $request->all()));
    }

    #[OA\Delete(
        path: "/api/employees/{id}",
        operationId: "destroyEmployee",
        description: "Delete an employee",
        summary: "Delete employee",
        tags: ["Employees"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Employee ID",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Employee deleted successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: ""),
                        new OA\Property(property: "data", type: "null")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Employee not found")
        ]
    )]
    public function destroy($id)
    {
        $employee = $this->employeeService->getEmployeeById($id);
        $user = auth()->user();

        if ($user && $user->hasRole('employee')) {
            return ApiResponse::forbidden('Employees cannot delete records');
        }

        return ApiResponse::success($this->employeeService->deleteEmployee($employee));
    }

    #[OA\Get(
        path: "/api/employees/email/{email}",
        operationId: "getEmployeeByEmail",
        description: "Get employee by email",
        summary: "Get employee by email",
        tags: ["Employees"],
        parameters: [
            new OA\Parameter(
                name: "email",
                in: "path",
                required: true,
                description: "Employee email",
                schema: new OA\Schema(type: "string", format: "email")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Employee retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: ""),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Employee not found")
        ]
    )]
    public function getEmployeeByEmail($email)
    {
        $user = auth()->user();
        if ($user && $user->hasRole('employee')) {
            if ($user->email !== $email) {
                return ApiResponse::forbidden('You can only access your own profile');
            }
        }
        return ApiResponse::success($this->employeeService->getEmployeeByEmail($email));
    }

    #[OA\Get(
        path: "/api/employees/department/{departmentId}",
        operationId: "getEmployeesByDepartment",
        description: "Get employees by department",
        summary: "Get employees by department",
        tags: ["Employees"],
        parameters: [
            new OA\Parameter(
                name: "departmentId",
                in: "path",
                required: true,
                description: "Department ID",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Employees retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: ""),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(type: "object")
                        )
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Department not found")
        ]
    )]
    public function getEmployeesByDepartment($departmentId)
    {
        $user = auth()->user();
        if ($user && $user->hasRole('employee')) {
            return ApiResponse::forbidden('Employees cannot view department rosters');
        }
        return ApiResponse::success(EmployeeResource::collection($this->employeeService->getEmployeesByDepartment($departmentId)));
    }
}

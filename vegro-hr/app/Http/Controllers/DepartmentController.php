<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Department;
use Illuminate\Http\Request;
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
        return ApiResponse::success(Department::all(), "Departments retrieved successfully");
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
        $validated = $request->validate(['name'=>'required|unique:departments']);
        return Department::create($validated);
        return ApiResponse::success(Department::create($validated), "Department created successfully", 201);        
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
        return $department;
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
        $validated = $request->validate(['name'=>'required|unique:departments,name,'.$department->id]);
        $department->update($validated);
        return $department;
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
        return ApiResponse::success(null, "Department deleted successfully");
    }
}
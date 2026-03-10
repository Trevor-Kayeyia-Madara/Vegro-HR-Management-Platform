<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Resources\PayrollResource;
use OpenApi\Attributes as OA;

#[OA\Info(title: "Payroll API", version: "1.0.0")]

class PayrollController extends Controller
{

    #[OA\Get(
        path: "/api/payrolls",
        operationId: "getPayrolls",
        description: "Get list of all payrolls",
        summary: "List all payrolls",
        tags: ["Payrolls"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Payrolls retrieved successfully",
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
                                    new OA\Property(property: "month", type: "integer"),
                                    new OA\Property(property: "year", type: "integer"),
                                    new OA\Property(property: "basic_salary", type: "number", format: "float"),
                                    new OA\Property(property: "allowances", type: "number", format: "float"),
                                    new OA\Property(property: "deductions", type: "number", format: "float"),
                                    new OA\Property(property: "tax", type: "number", format: "float"),
                                    new OA\Property(property: "net_salary", type: "number", format: "float"),
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
        return ApiResponse::success(PayrollResource::collection(Payroll::with('employee', 'payslip')->get()));   
    }

    #[OA\Post(
        path: "/api/payrolls",
        operationId: "storePayroll",
        description: "Create a new payroll",
        summary: "Create payroll",
        tags: ["Payrolls"],
        requestBody: new OA\RequestBody(
            description: "Payroll data",
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["employee_id", "month", "year", "basic_salary"],
                properties: [
                    new OA\Property(property: "employee_id", type: "integer", description: "Employee ID", example: 1),
                    new OA\Property(property: "month", type: "integer", description: "Month (1-12)", example: 3),
                    new OA\Property(property: "year", type: "integer", description: "Year", example: 2026),
                    new OA\Property(property: "basic_salary", type: "number", format: "float", description: "Basic salary", example: 5000.00),
                    new OA\Property(property: "allowances", type: "number", format: "float", description: "Allowances", example: 1000.00, nullable: true),
                    new OA\Property(property: "deductions", type: "number", format: "float", description: "Deductions", example: 500.00, nullable: true),
                    new OA\Property(property: "tax", type: "number", format: "float", description: "Tax", example: 300.00, nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Payroll created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Payroll created successfully"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer',
            'basic_salary' => 'required|numeric',
            'allowances' => 'numeric|nullable',
            'deductions' => 'numeric|nullable',
            'tax' => 'numeric|nullable'
        ]);

        // Calculate net salary
        $validated['net_salary'] = $validated['basic_salary'] 
            + ($validated['allowances'] ?? 0) 
            - ($validated['deductions'] ?? 0) 
            - ($validated['tax'] ?? 0);

        $payroll = Payroll::create($validated);

        return ApiResponse::success(new PayrollResource($payroll), "Payroll created successfully", 201);
    }

    #[OA\Get(
        path: "/api/payrolls/{id}",
        operationId: "getPayroll",
        description: "Get a specific payroll",
        summary: "Get payroll by ID",
        tags: ["Payrolls"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Payroll ID",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Payroll retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: ""),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Payroll not found")
        ]
    )]
    public function show(Payroll $payroll)
    {
        return ApiResponse::success(new PayrollResource($payroll->load('employee', 'payslip')));
    }

    #[OA\Put(
        path: "/api/payrolls/{id}",
        operationId: "updatePayroll",
        description: "Update a payroll",
        summary: "Update payroll",
        tags: ["Payrolls"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Payroll ID",
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            description: "Payroll data",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "basic_salary", type: "number", format: "float", description: "Basic salary", example: 5000.00, nullable: true),
                    new OA\Property(property: "allowances", type: "number", format: "float", description: "Allowances", example: 1000.00, nullable: true),
                    new OA\Property(property: "deductions", type: "number", format: "float", description: "Deductions", example: 500.00, nullable: true),
                    new OA\Property(property: "tax", type: "number", format: "float", description: "Tax", example: 300.00, nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Payroll updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Payroll updated successfully"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Payroll not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(Request $request, Payroll $payroll)
    {
        $validated = $request->validate([
            'basic_salary' => 'numeric|nullable',
            'allowances' => 'numeric|nullable',
            'deductions' => 'numeric|nullable',
            'tax' => 'numeric|nullable',
        ]);

        // Keep existing values if not provided
        $basic = $validated['basic_salary'] ?? $payroll->basic_salary;
        $allowances = $validated['allowances'] ?? $payroll->allowances;
        $deductions = $validated['deductions'] ?? $payroll->deductions;
        $tax = $validated['tax'] ?? $payroll->tax;

        $payroll->update([
            'basic_salary' => $basic,
            'allowances' => $allowances,
            'deductions' => $deductions,
            'tax' => $tax,
            'net_salary' => $basic + $allowances - $deductions - $tax
        ]);

        return ApiResponse::success(new PayrollResource($payroll), "Payroll updated successfully");
    }

    #[OA\Delete(
        path: "/api/payrolls/{id}",
        operationId: "destroyPayroll",
        description: "Delete a payroll",
        summary: "Delete payroll",
        tags: ["Payrolls"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Payroll ID",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Payroll deleted successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Payroll deleted successfully"),
                        new OA\Property(property: "data", type: "null")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Payroll not found")
        ]
    )]
    public function destroy(Payroll $payroll)
    {
        $payroll->delete();
        return ApiResponse::success(null, "Payroll deleted successfully");
    }

}
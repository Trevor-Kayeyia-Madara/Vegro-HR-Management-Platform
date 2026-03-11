<?php

namespace App\Http\Controllers;
use App\Services\PayslipService;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use OpenApi\Attributes as OA;
use App\Models\Employee;

class PayslipController extends Controller
{
    protected $payslipService;

    public function __construct(PayslipService $payslipService)
    {
        $this->payslipService = $payslipService;
    }

    #[OA\Get(
        path: "/api/payslips",
        operationId: "getPayslips",
        description: "Get list of all payslips",
        summary: "List all payslips",
        tags: ["Payslips"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Payslips retrieved successfully",
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
                                    new OA\Property(property: "payroll_id", type: "integer"),
                                    new OA\Property(property: "pdf_path", type: "string", nullable: true),
                                    new OA\Property(property: "generated_at", type: "string", format: "date-time", nullable: true),
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
        return ApiResponse::success($this->payslipService->getPayslipsPaginated($perPage), "Payslips retrieved successfully");
    }

    public function myPayslips(Request $request)
    {
        $user = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();
        if (!$employee) {
            return ApiResponse::forbidden('Employee profile not found.');
        }

        $perPage = max((int) $request->query('per_page', 10), 1);
        return ApiResponse::success(
            $this->payslipService->getPayslipsByEmployeePaginated($employee->id, $perPage),
            "Payslips retrieved successfully"
        );
    }

    #[OA\Get(
        path: "/api/payslips/{id}",
        operationId: "getPayslip",
        description: "Get a specific payslip",
        summary: "Get payslip by ID",
        tags: ["Payslips"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Payslip ID",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Payslip retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: ""),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Payslip not found")
        ]
    )]
    public function show($id)
    {
        return response()->json($this->payslipService->getPayslipById($id));
    }

    #[OA\Post(
        path: "/api/payslips",
        operationId: "storePayslip",
        description: "Create a new payslip",
        summary: "Create payslip",
        tags: ["Payslips"],
        requestBody: new OA\RequestBody(
            description: "Payslip data",
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["payroll_id"],
                properties: [
                    new OA\Property(property: "payroll_id", type: "integer", description: "Payroll ID", example: 1),
                    new OA\Property(property: "pdf_path", type: "string", description: "Stored PDF path", example: "payslips/payslip-1.pdf", nullable: true),
                    new OA\Property(property: "generated_at", type: "string", format: "date-time", description: "When the payslip was generated", example: "2026-03-09T19:05:42Z", nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Payslip created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Payslip created successfully"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(Request $request)
    {
        $data = $request->validate([
            'payroll_id' => 'required|integer|exists:payrolls,id',
            'pdf_path' => 'nullable|string|max:255',
        ]);

        return response()->json($this->payslipService->createPayslip($data), 201);
    }

    #[OA\Put(
        path: "/api/payslips/{id}",
        operationId: "updatePayslip",
        description: "Update a payslip",
        summary: "Update payslip",
        tags: ["Payslips"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Payslip ID",
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            description: "Payslip data",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "payroll_id", type: "integer", description: "Payroll ID", example: 1, nullable: true),
                    new OA\Property(property: "pdf_path", type: "string", description: "Stored PDF path", example: "payslips/payslip-1.pdf", nullable: true),
                    new OA\Property(property: "generated_at", type: "string", format: "date-time", description: "When the payslip was generated", example: "2026-03-09T19:05:42Z", nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Payslip updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Payslip updated successfully"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Payslip not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'pdf_path' => 'nullable|string|max:255',
        ]);

        return response()->json($this->payslipService->updatePayslip($id, $data));
    }

    public function approve($id, Request $request)
    {
        return response()->json($this->payslipService->approvePayslip($id, $request->user()->id));
    }

    public function issue($id)
    {
        return response()->json($this->payslipService->issuePayslip($id));
    }

    #[OA\Delete(
        path: "/api/payslips/{id}",
        operationId: "destroyPayslip",
        description: "Delete a payslip",
        summary: "Delete payslip",
        tags: ["Payslips"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Payslip ID",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: "Payslip deleted successfully"
            ),
            new OA\Response(response: 404, description: "Payslip not found")
        ]
    )]
    public function destroy($id)
    {
        $this->payslipService->deletePayslip($id);
        return response()->json(null, 204);
    }

    #[OA\Get(
        path: "/api/payslips/export/csv",
        operationId: "exportPayslipsToCSV",
        description: "Export payslips to CSV",
        summary: "Export payslips to CSV",
        tags: ["Payslips"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Payslips exported successfully",
                content: new OA\MediaType(
                    mediaType: "text/csv",
                    schema: new OA\Schema(type: "string", format: "binary")
                )
            )
        ]
    )]
    public function exportToCSV()
    {
        return $this->payslipService->exportPayslipsToCSV();
    }
}

<?php

namespace App\Http\Controllers;
use App\Services\AttendanceService;
use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Requests\UpdateAttendanceRequest;
use App\Models\Attendance; 
use App\Helpers\ApiResponse;
use OpenApi\Attributes as OA;

class AttendanceController extends Controller
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    #[OA\Get(
        path: "/api/attendance",
        operationId: "getAttendances",
        description: "Get list of all attendances",
        summary: "List all attendances",
        tags: ["Attendance"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Attendances retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Attendances retrieved successfully"),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer"),
                                    new OA\Property(property: "employee_id", type: "integer"),
                                    new OA\Property(property: "date", type: "string", format: "date"),
                                    new OA\Property(property: "status", type: "string", enum: ["present", "absent", "late", "excused"]),
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
        if ($user && $user->hasRole('manager')) {
            $attendances = $this->attendanceService->getAttendancesForManagerPaginated($user->id, $perPage);
            return ApiResponse::success($attendances, "Attendances retrieved successfully");
        }

        $attendances = $this->attendanceService->getAttendancesWithPagination($perPage);
        return ApiResponse::success($attendances, "Attendances retrieved successfully");
    }

    #[OA\Post(
        path: "/api/attendance",
        operationId: "storeAttendance",
        description: "Create a new attendance record",
        summary: "Create attendance",
        tags: ["Attendance"],
        requestBody: new OA\RequestBody(
            description: "Attendance data",
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["employee_id", "date", "status"],
                properties: [
                    new OA\Property(property: "employee_id", type: "integer", description: "Employee ID", example: 1),
                    new OA\Property(property: "date", type: "string", format: "date", description: "Attendance date", example: "2026-03-09"),
                    new OA\Property(property: "status", type: "string", description: "Attendance status", enum: ["present", "absent", "late", "excused"], example: "present")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Attendance created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Attendance created successfully"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Error creating attendance"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(StoreAttendanceRequest $request)
    {
        try {
            $attendance = $this->attendanceService->createAttendance($request->validated());
            return ApiResponse::success($attendance, "Attendance created successfully", 201);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    #[OA\Get(
        path: "/api/attendance/{id}",
        operationId: "getAttendance",
        description: "Get a specific attendance record",
        summary: "Get attendance by ID",
        tags: ["Attendance"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Attendance ID",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Attendance retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Attendance retrieved successfully"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Attendance not found")
        ]
    )]
    public function show($id)
    {
        $attendance = $this->attendanceService->getAttendanceById($id);
        if ($attendance) {
            return ApiResponse::success($attendance, "Attendance retrieved successfully");
        }
        return ApiResponse::notFound("Attendance not found");
    }

    #[OA\Put(
        path: "/api/attendance/{id}",
        operationId: "updateAttendance",
        description: "Update an attendance record",
        summary: "Update attendance",
        tags: ["Attendance"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Attendance ID",
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            description: "Attendance data",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "employee_id", type: "integer", description: "Employee ID", example: 1, nullable: true),
                    new OA\Property(property: "date", type: "string", format: "date", description: "Attendance date", example: "2026-03-09", nullable: true),
                    new OA\Property(property: "status", type: "string", description: "Attendance status", enum: ["present", "absent", "late", "excused"], example: "present", nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Attendance updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Attendance updated successfully"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Error updating attendance"),
            new OA\Response(response: 404, description: "Attendance not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(UpdateAttendanceRequest $request, Attendance $attendance)
    {
        try {
            $updatedAttendance = $this->attendanceService->updateAttendance($attendance, $request->validated());
            return ApiResponse::success($updatedAttendance, "Attendance updated successfully");
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    #[OA\Delete(
        path: "/api/attendance/{id}",
        operationId: "destroyAttendance",
        description: "Delete an attendance record",
        summary: "Delete attendance",
        tags: ["Attendance"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Attendance ID",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Attendance deleted successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Attendance deleted successfully"),
                        new OA\Property(property: "data", type: "null")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Error deleting attendance"),
            new OA\Response(response: 404, description: "Attendance not found")
        ]
    )]
    public function destroy(Attendance $attendance)
    {
        if ($this->attendanceService->deleteAttendance($attendance)) {
            return ApiResponse::success(null, "Attendance deleted successfully");
        }
        return ApiResponse::error("Failed to delete attendance", 400);
    }

    #[OA\Get(
        path: "/api/attendances/export/csv",
        operationId: "exportAttendancesToCSV",
        description: "Export attendances to CSV",
        summary: "Export attendances to CSV",
        tags: ["Attendance"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Attendances exported successfully",
                content: new OA\MediaType(
                    mediaType: "text/csv",
                    schema: new OA\Schema(type: "string", format: "binary")
                )
            )
        ]
    )]
    public function exportToCSV()
    {
        $csv = $this->attendanceService->exportAttendancesToCSV();

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="attendances.csv"');
    }

    #[OA\Post(
        path: "/api/attendances/import/csv",
        operationId: "importAttendancesFromCSV",
        description: "Import attendances from CSV",
        summary: "Import attendances from CSV",
        tags: ["Attendance"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Attendances imported successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Import complete"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function importFromCSV(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,txt',
            'mode' => 'nullable|in:upsert,skip',
        ]);

        $result = $this->attendanceService->importAttendancesFromCSV(
            $request->file('file'),
            $validated['mode'] ?? 'upsert'
        );

        return ApiResponse::success($result, 'Import complete');
    }
}

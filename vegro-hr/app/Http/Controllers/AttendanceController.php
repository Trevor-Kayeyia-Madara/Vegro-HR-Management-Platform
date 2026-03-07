<?php

namespace App\Http\Controllers;
use App\Services\AttendanceService;
use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Requests\UpdateAttendanceRequest;
use App\Models\Attendance; 
use App\Helpers\ApiResponse;

class AttendanceController extends Controller
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function index()
    {
        $attendances = $this->attendanceService->getAllAttendances();
        return ApiResponse::success($attendances, "Attendances retrieved successfully");
    }

    public function store(StoreAttendanceRequest $request)
    {
        try {
            $attendance = $this->attendanceService->createAttendance($request->validated());
            return ApiResponse::success($attendance, "Attendance created successfully", 201);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function show($id)
    {
        $attendance = $this->attendanceService->getAttendanceById($id);
        if ($attendance) {
            return ApiResponse::success($attendance, "Attendance retrieved successfully");
        }
        return ApiResponse::notFound("Attendance not found");
    }

    public function update(UpdateAttendanceRequest $request, Attendance $attendance)
    {
        try {
            $updatedAttendance = $this->attendanceService->updateAttendance($attendance, $request->validated());
            return ApiResponse::success($updatedAttendance, "Attendance updated successfully");
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function destroy(Attendance $attendance)
    {
        if ($this->attendanceService->deleteAttendance($attendance)) {
            return ApiResponse::success(null, "Attendance deleted successfully");
        }
        return ApiResponse::error("Failed to delete attendance", 400);
    }
}

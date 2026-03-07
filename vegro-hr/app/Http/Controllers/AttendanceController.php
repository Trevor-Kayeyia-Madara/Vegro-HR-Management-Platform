<?php

namespace App\Http\Controllers;

use App\Services\AttendanceService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function record(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'status' => 'required|in:present,absent,late,remote',
        ]);

        return $this->attendanceService->recordAttendance($validated);
    }

    public function update(Request $request, $attendance)
    {
        $validated = $request->validate([
            'date' => 'sometimes|required|date',
            'status' => 'sometimes|required|in:present,absent,late,remote',
        ]);

        return $this->attendanceService->updateAttendance($attendance, $validated);
    }

    public function delete($attendance)
    {
        return $this->attendanceService->deleteAttendance($attendance);
    }

    public function getByEmployee($employeeId)
    {
        return $this->attendanceService->getAttendanceByEmployee($employeeId);
    }

    public function getByDateRange(Request $request, $employeeId)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        return $this->attendanceService->getAttendanceByDateRange(
            $employeeId,
            $validated['start_date'],
            $validated['end_date']
        );
    }

    public function index()
    {
        return $this->attendanceService->getAllAttendances();
    }
}

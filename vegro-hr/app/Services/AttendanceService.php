<?php

namespace App\Services;

use App\Models\Attendance;

class AttendanceService
{
    public function recordAttendance(array $data)
    {
        return Attendance::create($data);
    }

    public function updateAttendance(Attendance $attendance, array $data)
    {
        $attendance->update($data);
        return $attendance;
    }

    public function deleteAttendance(Attendance $attendance)
    {
        return $attendance->delete();
    }

    public function getAttendanceByEmployee($employeeId)
    {
        return Attendance::where('employee_id', $employeeId)->get();
    }

    public function getAttendanceByDateRange($employeeId, $startDate, $endDate)
    {
        return Attendance::where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();
    }

    public function getAllAttendances()
    {
        return Attendance::with('employee')->get();
    }

    public function updateAttendanceStatus(Attendance $attendance, $status)
    {
        $attendance->status = $status;
        $attendance->save();
        return $attendance;
    }
}
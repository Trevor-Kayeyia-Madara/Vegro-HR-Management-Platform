<?php

namespace App\Repositories; 
use App\Models\Attendance;
class AttendanceRepository
{
    public function getAll()
    {
        return Attendance::with('employee')->get();
    }

    public function getPaginated($perPage = 15)
    {
        return Attendance::with('employee')->paginate($perPage);
    }

    public function create(array $data)
    {
        return Attendance::create($data);
    }

    public function update(Attendance $attendance, array $data)
    {
        $attendance->update($data);
        return $attendance;
    }

    public function delete(Attendance $attendance)
    {
        return $attendance->delete();
    }

    public function findById($id)
    {
        return Attendance::find($id);
    }

    public function findByEmployeeId($employeeId)
    {
        return Attendance::where('employee_id', $employeeId)->get();
    }
}

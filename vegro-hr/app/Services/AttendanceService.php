<?php

namespace App\Services;
use App\Repositories\AttendanceRepository;
use App\Repositories\EmployeeRepository;
use App\Models\Attendance;

class AttendanceService
{
    protected $attendanceRepository;
    protected $employeeRepository;

    public function __construct(AttendanceRepository $attendanceRepository, EmployeeRepository $employeeRepository)
    {
        $this->attendanceRepository = $attendanceRepository;
        $this->employeeRepository = $employeeRepository;
    }

    public function getAllAttendances()
    {
        return $this->attendanceRepository->getAll();
    }

    public function getAttendancesWithPagination($perPage = 15)
    {
        return $this->attendanceRepository->getPaginated($perPage);
    }

    public function createAttendance(array $data)
    {
        // Validate employee existence
        if (!$this->employeeRepository->findById($data['employee_id'])) {
            throw new \Exception('Employee not found');
        }
        return $this->attendanceRepository->create($data);
    }

    public function updateAttendance(Attendance $attendance, array $data)
    {
        // Validate employee existence if employee_id is being updated
        if (isset($data['employee_id']) && !$this->employeeRepository->findById($data['employee_id'])) {
            throw new \Exception('Employee not found');
        }
        return $this->attendanceRepository->update($attendance, $data);
    }

    public function deleteAttendance(Attendance $attendance)
    {
        return $this->attendanceRepository->delete($attendance);
    }

    public function getAttendanceById($id)
    {
        return $this->attendanceRepository->findById($id);
    }

    public function getAttendancesByEmployeeId($employeeId)
    {
        return $this->attendanceRepository->findByEmployeeId($employeeId);
    }

    public function getAttendancesForManagerPaginated($managerId, $perPage = 15)
    {
        $departmentIds = \App\Models\Department::where('manager_id', $managerId)->pluck('id');
        if ($departmentIds->isEmpty()) {
            return \App\Models\Attendance::whereRaw('1=0')->paginate($perPage);
        }

        return \App\Models\Attendance::with('employee')
            ->whereHas('employee', function ($query) use ($departmentIds) {
                $query->whereIn('department_id', $departmentIds);
            })
            ->paginate($perPage);
    }
}

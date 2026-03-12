<?php

namespace App\Services;
use App\Repositories\AttendanceRepository;
use App\Repositories\EmployeeRepository;
use App\Models\Attendance;
use App\Models\Employee;
use App\Helpers\CsvHelper;
use Illuminate\Http\UploadedFile;

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

    public function exportAttendancesToCSV(): string
    {
        $attendances = Attendance::with('employee')->get();
        $header = [
            'employee_id',
            'employee_number',
            'employee_name',
            'employee_email',
            'date',
            'status',
            'clock_in',
            'clock_out',
        ];

        $csv = CsvHelper::row($header);

        foreach ($attendances as $attendance) {
            $employee = $attendance->employee;
            $csv .= CsvHelper::row([
                $attendance->employee_id,
                $employee?->employee_number,
                $employee?->name,
                $employee?->email,
                $attendance->date,
                $attendance->status,
                $attendance->clock_in,
                $attendance->clock_out,
            ]);
        }

        return $csv;
    }

    public function importAttendancesFromCSV(UploadedFile $file, string $mode = 'upsert'): array
    {
        $path = $file->getRealPath();
        $csv = new \SplFileObject($path);
        $csv->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);

        $header = null;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];
        $rowNumber = 0;

        foreach ($csv as $row) {
            $rowNumber++;
            if ($row === [null] || $row === false) {
                continue;
            }

            if ($header === null) {
                $header = array_map(fn ($value) => CsvHelper::normalizeHeader((string) $value), $row);
                continue;
            }

            $data = [];
            foreach ($header as $index => $key) {
                if ($key === '') {
                    continue;
                }
                $data[$key] = isset($row[$index]) ? trim((string) $row[$index]) : null;
            }

            $hasContent = collect($data)->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty();
            if (!$hasContent) {
                continue;
            }

            try {
                $employeeId = $data['employee_id'] ?? null;
                if (!$employeeId && !empty($data['employee_email'])) {
                    $employeeId = Employee::where('email', $data['employee_email'])->value('id');
                }
                if (!$employeeId && !empty($data['employee_number'])) {
                    $employeeId = Employee::where('employee_number', $data['employee_number'])->value('id');
                }

                if (!$employeeId) {
                    throw new \Exception('Employee not found');
                }

                $date = $data['date'] ?? null;
                if (!$date) {
                    throw new \Exception('Missing date');
                }

                $payload = [
                    'employee_id' => (int) $employeeId,
                    'date' => $date,
                    'status' => $data['status'] ?? 'present',
                    'clock_in' => $data['clock_in'] ?? null,
                    'clock_out' => $data['clock_out'] ?? null,
                ];

                $existing = Attendance::where('employee_id', $payload['employee_id'])
                    ->where('date', $payload['date'])
                    ->first();

                if ($existing) {
                    if ($mode === 'skip') {
                        $skipped++;
                        continue;
                    }
                    $this->updateAttendance($existing, $payload);
                    $updated++;
                } else {
                    $this->createAttendance($payload);
                    $created++;
                }
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = [
                    'row' => $rowNumber,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }
}

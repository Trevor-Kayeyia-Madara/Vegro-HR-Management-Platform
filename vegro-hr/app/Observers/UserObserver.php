<?php

namespace App\Observers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;

class UserObserver
{
    public function saved(User $user): void
    {
        $user->loadMissing('role', 'employee');

        $roleTitle = strtolower(trim((string) ($user->role?->title ?? '')));
        $roleTitle = str_replace([' ', '-', '_'], '', $roleTitle);
        $isProtectedAdmin = in_array($roleTitle, ['superadmin', 'companyadmin'], true);

        $employee = $user->employee;

        // If user has no employee row, auto-create one for non-protected roles.
        if (!$employee && !$isProtectedAdmin && $user->company_id) {
            $defaultDepartmentId = Department::where('company_id', $user->company_id)->value('id');

            $employee = Employee::where('company_id', $user->company_id)
                ->where(function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->orWhere('email', $user->email);
                })
                ->first();

            $employeeNumber = 'EMP' . now()->format('Ymd') . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);

            if (!$employee) {
                $employee = Employee::create([
                    'company_id' => $user->company_id,
                    'employee_number' => $employeeNumber,
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'department_id' => $defaultDepartmentId,
                    'position' => $user->role?->title ?? 'Employee',
                    'salary' => 0,
                    'hire_date' => now()->toDateString(),
                    'status' => 'active',
                ]);
            } else {
                $employee->update([
                    'company_id' => $employee->company_id ?: $user->company_id,
                    'user_id' => $user->id,
                ]);
            }
        }

        if (!$employee) {
            return;
        }

        $employeePayload = [];

        if ((string) $employee->name !== (string) $user->name) {
            $employeePayload['name'] = $user->name;
        }

        if ((string) $employee->email !== (string) $user->email) {
            $employeePayload['email'] = $user->email;
        }

        $position = $user->role?->title ?? 'Employee';
        if ((string) $employee->position !== (string) $position) {
            $employeePayload['position'] = $position;
        }

        if (!empty($employeePayload)) {
            $employee->update($employeePayload);
        }

        if ($user->role_id) {
            $employee->roles()->sync([$user->role_id]);
        }
    }
}


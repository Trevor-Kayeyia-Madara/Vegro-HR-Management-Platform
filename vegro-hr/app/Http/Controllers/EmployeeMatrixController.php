<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Employee;
use App\Models\EmployeeManagerAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmployeeMatrixController extends Controller
{
    public function index(Employee $employee)
    {
        $user = auth()->user();
        if ($user && $user->hasRole('employee') && (int) $employee->user_id !== (int) $user->id) {
            return ApiResponse::forbidden('You can only access your own reporting lines');
        }

        $assignments = EmployeeManagerAssignment::with('manager:id,name,email')
            ->where('employee_id', $employee->id)
            ->orderBy('relationship_type')
            ->orderBy('created_at', 'desc')
            ->get();

        return ApiResponse::success($assignments, 'Reporting lines retrieved');
    }

    public function sync(Request $request, Employee $employee)
    {
        $companyId = $request->attributes->get('company_id') ?? auth()->user()?->company_id;

        $validated = $request->validate([
            'functional_manager_ids' => 'nullable|array',
            'functional_manager_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where('company_id', $companyId),
            ],
            'dotted_manager_ids' => 'nullable|array',
            'dotted_manager_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where('company_id', $companyId),
            ],
        ]);

        $functional = array_values(array_unique(array_map('intval', $validated['functional_manager_ids'] ?? [])));
        $dotted = array_values(array_unique(array_map('intval', $validated['dotted_manager_ids'] ?? [])));

        DB::transaction(function () use ($companyId, $employee, $functional, $dotted) {
            EmployeeManagerAssignment::where('employee_id', $employee->id)
                ->whereIn('relationship_type', ['functional', 'dotted'])
                ->delete();

            foreach ($functional as $managerId) {
                EmployeeManagerAssignment::create([
                    'company_id' => $companyId,
                    'employee_id' => $employee->id,
                    'manager_user_id' => $managerId,
                    'relationship_type' => 'functional',
                ]);
            }

            foreach ($dotted as $managerId) {
                EmployeeManagerAssignment::create([
                    'company_id' => $companyId,
                    'employee_id' => $employee->id,
                    'manager_user_id' => $managerId,
                    'relationship_type' => 'dotted',
                ]);
            }
        });

        $assignments = EmployeeManagerAssignment::with('manager:id,name,email')
            ->where('employee_id', $employee->id)
            ->orderBy('relationship_type')
            ->get();

        return ApiResponse::success($assignments, 'Reporting lines updated');
    }
}


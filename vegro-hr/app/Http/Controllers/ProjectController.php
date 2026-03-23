<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectMembership;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $perPage = max((int) $request->query('per_page', 10), 1);

        $projects = Project::withCount('memberships')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return ApiResponse::success($projects, 'Projects retrieved');
    }

    public function store(Request $request)
    {
        $companyId = $request->attributes->get('company_id') ?? auth()->user()?->company_id;

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('projects', 'name')->where('company_id', $companyId),
            ],
            'description' => 'nullable|string|max:2000',
            'status' => 'nullable|in:active,archived',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $project = Project::create(array_merge($validated, ['company_id' => $companyId]));

        return ApiResponse::success($project, 'Project created', 201);
    }

    public function show(Project $project)
    {
        $project->load([
            'memberships.employee:id,user_id,name,email,department_id,position',
            'memberships.reportsTo:id,name,email',
        ]);
        $project->loadCount('memberships');

        return ApiResponse::success($project, 'Project retrieved');
    }

    public function update(Request $request, Project $project)
    {
        $companyId = $request->attributes->get('company_id') ?? auth()->user()?->company_id;

        $validated = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('projects', 'name')
                    ->where('company_id', $companyId)
                    ->ignore($project->id),
            ],
            'description' => 'nullable|string|max:2000',
            'status' => 'nullable|in:active,archived',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $project->update($validated);

        return ApiResponse::success($project, 'Project updated');
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return ApiResponse::success(null, 'Project deleted');
    }

    public function addMember(Request $request, Project $project)
    {
        $companyId = $request->attributes->get('company_id') ?? auth()->user()?->company_id;

        $validated = $request->validate([
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->where('company_id', $companyId),
            ],
            'reports_to_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('company_id', $companyId),
            ],
            'role_title' => 'nullable|string|max:255',
            'allocation_percent' => 'nullable|integer|min:0|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $membership = ProjectMembership::updateOrCreate(
            [
                'company_id' => $companyId,
                'project_id' => $project->id,
                'employee_id' => (int) $validated['employee_id'],
            ],
            [
                'reports_to_user_id' => $validated['reports_to_user_id'] ?? null,
                'role_title' => $validated['role_title'] ?? null,
                'allocation_percent' => $validated['allocation_percent'] ?? null,
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
            ]
        );

        $membership->load('employee:id,user_id,name,email,department_id,position', 'reportsTo:id,name,email');

        return ApiResponse::success($membership, 'Member added', 201);
    }

    public function updateMember(Request $request, Project $project, ProjectMembership $membership)
    {
        if ((int) $membership->project_id !== (int) $project->id) {
            return ApiResponse::notFound('Project member not found');
        }

        $companyId = $request->attributes->get('company_id') ?? auth()->user()?->company_id;

        $validated = $request->validate([
            'reports_to_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('company_id', $companyId),
            ],
            'role_title' => 'nullable|string|max:255',
            'allocation_percent' => 'nullable|integer|min:0|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $membership->update($validated);
        $membership->load('employee:id,user_id,name,email,department_id,position', 'reportsTo:id,name,email');

        return ApiResponse::success($membership, 'Member updated');
    }

    public function removeMember(Project $project, ProjectMembership $membership)
    {
        if ((int) $membership->project_id !== (int) $project->id) {
            return ApiResponse::notFound('Project member not found');
        }

        $membership->delete();
        return ApiResponse::success(null, 'Member removed');
    }
}


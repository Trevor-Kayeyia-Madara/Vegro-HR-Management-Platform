<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Employee;
use App\Models\EmployeeOnboardingDocument;
use App\Models\OnboardingDocumentTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OnboardingDocumentController extends Controller
{
    public function templates(Request $request)
    {
        $perPage = max((int) $request->query('per_page', 20), 1);
        $q = trim((string) $request->query('q', ''));

        $query = OnboardingDocumentTemplate::query()->orderByDesc('created_at');
        if ($q !== '') {
            $query->where('title', 'like', '%' . $q . '%');
        }

        return ApiResponse::success($query->paginate($perPage), 'Onboarding templates retrieved');
    }

    public function createTemplate(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return ApiResponse::unauthorized('Unauthorized');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'nullable|string|max:100',
            'content' => 'nullable|string|max:100000',
            'document' => 'nullable|file|mimes:pdf,doc,docx,png,jpg,jpeg|max:10240',
            'requires_signature' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        if (empty($validated['content']) && !$request->hasFile('document')) {
            return ApiResponse::error('Provide document text or upload a file', 422);
        }

        $storedFile = null;
        if ($request->hasFile('document')) {
            $storedFile = $request->file('document')->store("drive/{$user->company_id}", 'public');
        }

        $template = OnboardingDocumentTemplate::create([
            'company_id' => $user->company_id,
            'title' => $validated['title'],
            'type' => $validated['type'] ?? 'contract',
            'content' => $validated['content'] ?? '',
            'file_name' => $request->file('document')?->getClientOriginalName(),
            'file_path' => $storedFile,
            'file_mime' => $request->file('document')?->getClientMimeType(),
            'file_size' => $request->file('document')?->getSize(),
            'requires_signature' => $validated['requires_signature'] ?? true,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        app(\App\Services\ActivityLogService::class)->log(
            'onboarding.template.created',
            (int) $user->company_id,
            OnboardingDocumentTemplate::class,
            $template->id,
            ['template_id' => $template->id, 'title' => $template->title]
        );

        return ApiResponse::success($template, 'Drive document created', 201);
    }

    public function updateTemplate(Request $request, OnboardingDocumentTemplate $template)
    {
        $user = auth()->user();
        if (!$user) {
            return ApiResponse::unauthorized('Unauthorized');
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'type' => 'nullable|string|max:100',
            'content' => 'nullable|string|max:100000',
            'document' => 'nullable|file|mimes:pdf,doc,docx,png,jpg,jpeg|max:10240',
            'requires_signature' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        if ($request->hasFile('document')) {
            if ($template->file_path) {
                Storage::disk('public')->delete($template->file_path);
            }

            $path = $request->file('document')->store("drive/{$user->company_id}", 'public');
            $validated['file_name'] = $request->file('document')->getClientOriginalName();
            $validated['file_path'] = $path;
            $validated['file_mime'] = $request->file('document')->getClientMimeType();
            $validated['file_size'] = $request->file('document')->getSize();
        }

        if (array_key_exists('content', $validated) && $validated['content'] === null) {
            $validated['content'] = '';
        }

        $template->update($validated);

        app(\App\Services\ActivityLogService::class)->log(
            'onboarding.template.updated',
            (int) $user->company_id,
            OnboardingDocumentTemplate::class,
            $template->id,
            ['template_id' => $template->id, 'title' => $template->title]
        );

        return ApiResponse::success($template, 'Drive document updated');
    }

    public function deleteTemplate(Request $request, OnboardingDocumentTemplate $template)
    {
        $user = auth()->user();
        if (!$user) {
            return ApiResponse::unauthorized('Unauthorized');
        }

        $templateId = $template->id;
        if ($template->file_path) {
            Storage::disk('public')->delete($template->file_path);
        }
        $template->delete();

        app(\App\Services\ActivityLogService::class)->log(
            'onboarding.template.deleted',
            (int) $user->company_id,
            OnboardingDocumentTemplate::class,
            $templateId,
            ['template_id' => $templateId]
        );

        return ApiResponse::success(null, 'Drive document deleted');
    }

    public function assignments(Request $request)
    {
        $perPage = max((int) $request->query('per_page', 20), 1);
        $status = trim((string) $request->query('status', ''));

        $query = EmployeeOnboardingDocument::query()
            ->with(['employee:id,name,email', 'template:id,title,type,requires_signature,file_name,file_path,file_mime,file_size'])
            ->orderByDesc('created_at');

        if ($status !== '') {
            $query->where('status', $status);
        }

        return ApiResponse::success($query->paginate($perPage), 'Onboarding assignments retrieved');
    }

    public function myAssignments(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return ApiResponse::unauthorized('Unauthorized');
        }

        $employee = Employee::where('user_id', $user->id)->first();
        if (!$employee) {
            return ApiResponse::success(collect([]), 'No employee profile found');
        }

        $perPage = max((int) $request->query('per_page', 20), 1);
        $items = EmployeeOnboardingDocument::query()
            ->with(['template:id,title,type,content,requires_signature,file_name,file_path,file_mime,file_size'])
            ->where('employee_id', $employee->id)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return ApiResponse::success($items, 'My onboarding assignments retrieved');
    }

    public function downloadTemplate(Request $request, OnboardingDocumentTemplate $template)
    {
        $user = auth()->user();
        if (!$user) {
            return ApiResponse::unauthorized('Unauthorized');
        }

        if (!$template->file_path || !Storage::disk('public')->exists($template->file_path)) {
            return ApiResponse::notFound('Document file not found');
        }

        return Storage::disk('public')->download(
            $template->file_path,
            $template->file_name ?: basename($template->file_path)
        );
    }

    public function assign(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return ApiResponse::unauthorized('Unauthorized');
        }

        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
            'template_id' => 'required|integer|exists:onboarding_document_templates,id',
            'due_date' => 'nullable|date',
        ]);

        $assignment = EmployeeOnboardingDocument::create([
            'company_id' => $user->company_id,
            'employee_id' => $validated['employee_id'],
            'template_id' => $validated['template_id'],
            'due_date' => $validated['due_date'] ?? null,
            'status' => 'pending',
        ]);

        app(\App\Services\ActivityLogService::class)->log(
            'onboarding.assignment.created',
            (int) $user->company_id,
            EmployeeOnboardingDocument::class,
            $assignment->id,
            [
                'assignment_id' => $assignment->id,
                'employee_id' => $assignment->employee_id,
                'template_id' => $assignment->template_id,
            ]
        );

        return ApiResponse::success(
            $assignment->load(['employee:id,name,email', 'template:id,title,type,file_name,file_path,file_mime,file_size']),
            'Onboarding document assigned',
            201
        );
    }

    public function sign(Request $request, EmployeeOnboardingDocument $assignment)
    {
        $user = auth()->user();
        if (!$user) {
            return ApiResponse::unauthorized('Unauthorized');
        }

        $employee = Employee::where('user_id', $user->id)->first();
        if (!$employee || (int) $assignment->employee_id !== (int) $employee->id) {
            return ApiResponse::forbidden('You can only sign your assigned documents');
        }

        $validated = $request->validate([
            'signature_name' => 'required|string|max:255',
        ]);

        $assignment->update([
            'signature_name' => $validated['signature_name'],
            'signed_at' => now(),
            'signed_ip' => (string) $request->ip(),
            'signed_user_agent' => substr((string) $request->userAgent(), 0, 512),
            'status' => 'signed',
        ]);

        app(\App\Services\ActivityLogService::class)->log(
            'onboarding.assignment.signed',
            (int) $user->company_id,
            EmployeeOnboardingDocument::class,
            $assignment->id,
            [
                'assignment_id' => $assignment->id,
                'employee_id' => $assignment->employee_id,
                'signed_at' => optional($assignment->signed_at)->toDateTimeString(),
            ]
        );

        return ApiResponse::success($assignment->fresh(['template:id,title,type']), 'Document signed successfully');
    }
}

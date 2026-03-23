<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\EmployeeFeedback;
use Illuminate\Http\Request;

class EmployeeFeedbackController extends Controller
{
    public function index(Request $request)
    {
        $perPage = max((int) $request->query('per_page', 10), 1);
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $category = trim((string) $request->query('category', ''));

        $query = EmployeeFeedback::query()
            ->with(['submitter:id,name,email', 'reviewer:id,name,email'])
            ->orderByDesc('created_at');

        if ($q !== '') {
            $query->where(function ($builder) use ($q) {
                $builder
                    ->where('subject', 'like', '%' . $q . '%')
                    ->orWhere('message', 'like', '%' . $q . '%');
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($category !== '') {
            $query->where('category', $category);
        }

        return ApiResponse::success($query->paginate($perPage), 'Feedback list retrieved');
    }

    public function mine(Request $request)
    {
        $perPage = max((int) $request->query('per_page', 10), 1);
        $userId = auth()->id();

        $items = EmployeeFeedback::query()
            ->with(['reviewer:id,name,email'])
            ->where('submitted_by', $userId)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return ApiResponse::success($items, 'My feedback retrieved');
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return ApiResponse::unauthorized('Unauthorized');
        }

        $validated = $request->validate([
            'category' => 'nullable|string|in:general,payroll,leaves,manager,workplace,other',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $feedback = EmployeeFeedback::create([
            'company_id' => $user->company_id,
            'submitted_by' => $user->id,
            'category' => $validated['category'] ?? 'general',
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'status' => 'open',
        ]);

        app(\App\Services\ActivityLogService::class)->log(
            'feedback.submitted',
            (int) $user->company_id,
            EmployeeFeedback::class,
            $feedback->id,
            [
                'feedback_id' => $feedback->id,
                'category' => $feedback->category,
                'status' => $feedback->status,
            ]
        );

        return ApiResponse::success($feedback, 'Feedback submitted', 201);
    }

    public function update(Request $request, EmployeeFeedback $feedback)
    {
        $user = auth()->user();
        if (!$user) {
            return ApiResponse::unauthorized('Unauthorized');
        }

        $validated = $request->validate([
            'status' => 'sometimes|required|string|in:open,in_review,resolved,closed',
            'response_message' => 'nullable|string|max:5000',
        ]);

        if (array_key_exists('status', $validated)) {
            $feedback->status = $validated['status'];
            $feedback->reviewed_at = now();
            $feedback->reviewed_by = $user->id;
        }

        if (array_key_exists('response_message', $validated)) {
            $feedback->response_message = $validated['response_message'];
            $feedback->reviewed_at = now();
            $feedback->reviewed_by = $user->id;
        }

        $feedback->save();

        app(\App\Services\ActivityLogService::class)->log(
            'feedback.updated',
            (int) $user->company_id,
            EmployeeFeedback::class,
            $feedback->id,
            [
                'feedback_id' => $feedback->id,
                'status' => $feedback->status,
                'reviewed_by' => $feedback->reviewed_by,
            ]
        );

        return ApiResponse::success(
            $feedback->load(['submitter:id,name,email', 'reviewer:id,name,email']),
            'Feedback updated'
        );
    }
}

<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\LeadCapture;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use OpenApi\Attributes as OA;

class LeadCaptureController extends Controller
{
    #[OA\Post(
        path: "/api/lead-capture",
        operationId: "leadCapture",
        description: "Capture demo waitlist leads",
        summary: "Capture lead",
        tags: ["Leads"],
        requestBody: new OA\RequestBody(
            description: "Lead data",
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["name", "email"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Jane Doe"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "jane@company.com"),
                    new OA\Property(property: "company", type: "string", example: "Acme Inc"),
                    new OA\Property(property: "message", type: "string", example: "We want payroll and reporting demo"),
                    new OA\Property(property: "source", type: "string", example: "landing-page"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Lead captured successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "success"),
                        new OA\Property(property: "message", type: "string", example: "Lead captured"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'company' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:2000',
            'source' => 'nullable|string|max:100',
        ]);

        $lead = LeadCapture::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'company' => $validated['company'] ?? null,
            'message' => $validated['message'] ?? null,
            'source' => $validated['source'] ?? 'landing-page',
        ]);

        $this->notifyLeadRecipients($lead);

        return ApiResponse::success($lead, 'Lead captured successfully', 201);
    }

    protected function notifyLeadRecipients(LeadCapture $lead): void
    {
        $recipients = collect(explode(',', (string) env('LEAD_CAPTURE_RECIPIENTS', 'invodtech@gmail.com,info@invodtechltd.com')))
            ->map(fn ($email) => trim((string) $email))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();

        if (empty($recipients)) {
            return;
        }

        $subject = 'Vegro HR Demo Request - ' . ($lead->company ?: $lead->name);
        $body = "New demo request received.\n\n"
            . "Name: {$lead->name}\n"
            . "Email: {$lead->email}\n"
            . "Company: " . ($lead->company ?: '-') . "\n"
            . "Source: " . ($lead->source ?: '-') . "\n"
            . "Message:\n" . ($lead->message ?: '-') . "\n\n"
            . "Submitted at: " . optional($lead->created_at)->toDateTimeString();

        try {
            Mail::raw($body, function ($message) use ($recipients, $subject) {
                $message->to($recipients)->subject($subject);
            });
        } catch (\Throwable $exception) {
            Log::warning('Lead capture email notification failed', [
                'lead_id' => $lead->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}

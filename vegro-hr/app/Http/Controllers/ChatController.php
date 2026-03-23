<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Services\ChatService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(protected ChatService $chatService)
    {
    }

    public function users(Request $request)
    {
        return ApiResponse::success(
            $this->chatService->listAvailableUsers($request->user())
        );
    }

    public function conversations(Request $request)
    {
        $perPage = max((int) $request->query('per_page', 20), 1);
        return ApiResponse::success(
            $this->chatService->listConversations($request->user(), $perPage)
        );
    }

    public function createConversation(Request $request)
    {
        $validated = $request->validate([
            'participant_ids' => ['required', 'array', 'min:1'],
            'participant_ids.*' => ['required', 'integer', 'exists:users,id'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);

        try {
            $conversation = $this->chatService->createConversation(
                $request->user(),
                $validated['participant_ids'],
                $validated['name'] ?? null
            );
        } catch (\RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }

        return ApiResponse::success($conversation, 'Conversation created', 201);
    }

    public function showConversation(Request $request, int $conversationId)
    {
        try {
            $conversation = $this->chatService->getConversation($request->user(), $conversationId);
        } catch (\RuntimeException $exception) {
            return ApiResponse::forbidden($exception->getMessage());
        }

        return ApiResponse::success($conversation);
    }

    public function messages(Request $request, int $conversationId)
    {
        $perPage = max((int) $request->query('per_page', 30), 1);

        try {
            $messages = $this->chatService->getMessages($request->user(), $conversationId, $perPage);
        } catch (\RuntimeException $exception) {
            return ApiResponse::forbidden($exception->getMessage());
        }

        return ApiResponse::success($messages);
    }

    public function sendMessage(Request $request, int $conversationId)
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        try {
            $message = $this->chatService->sendMessage(
                $request->user(),
                $conversationId,
                $validated['body']
            );
        } catch (\RuntimeException $exception) {
            return ApiResponse::forbidden($exception->getMessage());
        }

        return ApiResponse::success($message, 'Message sent', 201);
    }
}


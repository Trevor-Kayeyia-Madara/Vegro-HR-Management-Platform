<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\SessionsService;

class SessionsController extends Controller
{
    protected $sessionsService;

    public function __construct(SessionsService $sessionsService)
    {
        $this->sessionsService = $sessionsService;
    }

    public function createSession(Request $request)
    {
        $userId = $request->input('user_id');
        $sessionToken = bin2hex(random_bytes(16)); // Generate a random session token
        $expiresAt = now()->addHours(2); // Set session expiration time

        $session = $this->sessionsService->createSession($userId, $sessionToken, $expiresAt);

        return response()->json(['session_token' => $session->session_token], 201);
    }

    public function getSessionByToken($sessionToken)
    {
        $session = $this->sessionsService->getSessionByToken($sessionToken);

        if (!$session) {
            return response()->json(['message' => 'Session not found'], 404);
        }

        return response()->json($session);
    }

    public function deleteSession($sessionToken)
    {
        $deleted = $this->sessionsService->deleteSession($sessionToken);

        if ($deleted) {
            return response()->json(['message' => 'Session deleted successfully']);
        } else {
            return response()->json(['message' => 'Session not found'], 404);
        }
    }
}
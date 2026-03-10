<?php

namespace App\Services;

use App\Repositories\SessionsRepository;

class SessionsService
{
    protected $sessionsRepository;

    public function __construct(SessionsRepository $sessionsRepository)
    {
        $this->sessionsRepository = $sessionsRepository;
    }

    /**
     * Handle the logic for creating a session.
     */
    public function createSession(int $userId, string $sessionToken, $expiresAt, array $payload = [])
    {
        // You can add business logic here, like checking if a user 
        // already has too many active sessions.
        return $this->sessionsRepository->create([
            'user_id'       => $userId,
            'session_token' => $sessionToken,
            'expires_at'    => $expiresAt,
            'payload'       => $payload,
        ]);
    }

    public function getSessionByToken(string $sessionToken)
    {
        return $this->sessionsRepository->findByToken($sessionToken);
    }

    public function deleteExpired()
    {
        // This assumes your Repository has a deleteByToken method
        return $this->sessionsRepository->deleteExpired();
    }
}
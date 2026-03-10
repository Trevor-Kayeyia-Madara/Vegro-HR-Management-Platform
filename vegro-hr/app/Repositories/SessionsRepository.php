<?php

namespace App\Repositories;

use App\Models\Sessions;

class SessionsRepository
{
    protected $model;

    public function __construct(Sessions $sessions)
    {
        $this->model = $sessions;
    }

    /**
     * Find a session by its token.
     */
    public function findByToken(string $token)
    {
        return $this->model->where('session_token', $token)->first();
    }

    /**
     * Create a new session record.
     */
    public function create(array $data)
    {
        return $this->model->create($data);
    }

    /**
     * Delete expired sessions.
     */
    public function deleteExpired()
    {
        return $this->model->where('expires_at', '<', now())->delete();
    }
}
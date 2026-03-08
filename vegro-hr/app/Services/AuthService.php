<?php

namespace App\Services;
use App\Repositories\AuthRepository;

class AuthService
{
    protected $authRepository;

    public function __construct(AuthRepository $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    public function register(array $data)
    {
        return $this->authRepository->register($data);
    }

    public function login(array $credentials)
    {
        return $this->authRepository->login($credentials);
    }

    public function logout($user)
    {
        return $this->authRepository->logout($user);
    }
}
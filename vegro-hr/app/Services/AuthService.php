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

    public function store(array $data)
    {
        return $this->authRepository->store($data);
    }

    public function login(array $credentials)
    {
        return $this->authRepository->login($credentials);
    }

    public function logout($user)
    {
        return $this->authRepository->logout($user);
    }

    public function getRoleIdByName($name)
    {
        return $this->authRepository->getRoleIdByName($name);
    }

    public function getUserByEmail($email)
    {
        return $this->authRepository->getUserByEmail($email);
    }
}
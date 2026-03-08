<?php

namespace App\Repositories;
use App\Models\User;
use App\Repositories\RoleRepository;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AuthRepository
{
    protected $roleRepository;

    public function __construct(RoleRepository $roleRepository)
    {
        $this->roleRepository = $roleRepository;
    }

    public function register(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // Assign default role to the user (e.g., 'user')
        $defaultRole = $this->roleRepository->findByName('user');
        if ($defaultRole) {
            $user->roles()->attach($defaultRole->id);
        }

        return $user;
    }

    public function login(array $credentials)
    {
        $user = User::where('email', $credentials['email'])->first();

        if ($user && Hash::check($credentials['password'], $user->password)) {
            // Generate and return an authentication token (e.g., JWT)
            return $user->createToken('auth_token')->plainTextToken;
        }

        return null;
    }

    public function logout($user)
    {
        // Revoke the user's authentication token
        $user->tokens()->delete();
    }

}

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

    public function store(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role_id' => $this->roleRepository->getRoleIdByName('employee'), // Default role
            'password' => Hash::make($data['password']),
        ]);

        return $user;
    }

    public function login(array $credentials)
    {
        $user = User::where('email', $credentials['email'])->first();

        if ($user && Hash::check($credentials['password'], $user->password)) {
            // Generate a new authentication token for the user
            $token = $user->createToken('auth_token')->plainTextToken;
            return ['user' => $user, 'token' => $token];
        }

        if ($user) {
            return ['error' => 'Invalid password'];
        }
        return null;
    }

    public function logout($user)
    {
        // Revoke the user's authentication token
        $user->tokens()->delete();
    }

    public function getRoleIdByName($name)
    {
        $role = Role::where('name', $name)->first();
        return $role ? $role->id : null;
    }

    public function getUserByEmail($email)
    {
        return User::where('email', $email)->first();
    }

}

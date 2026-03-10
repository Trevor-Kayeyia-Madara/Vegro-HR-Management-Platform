<?php

namespace App\Repositories;
use App\Models\User;
use App\Models\ApiToken;
use App\Repositories\RoleRepository;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
            // Generate a simple token
            $token = hash_hmac('sha256', Str::random(32) . $user->id, config('app.key'));
            
            // Store token in database
            ApiToken::create([
                'user_id' => $user->id,
                'token' => $token,
                'expires_at' => now()->addDays(30),
            ]);
            
            return ['user' => $user, 'token' => $token];
        }

        if ($user) {
            return ['error' => 'Invalid password'];
        }
        return null;
    }

    public function logout($user)
    {
        // Delete user's tokens
        ApiToken::where('user_id', $user->id)->delete();
        return true;
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

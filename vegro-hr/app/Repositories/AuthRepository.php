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
        $companyId = $data['company_id'] ?? null;
        if (!$companyId && !empty($data['company_domain'])) {
            $companyId = \App\Models\Company::where('domain', $data['company_domain'])->value('id');
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role_id' => $this->roleRepository->getRoleIdByName('employee'), // Default role
            'password' => Hash::make($data['password']),
            'company_id' => $companyId,
        ]);

        return $user;
    }

    public function login(array $credentials)
    {
        $emailVerificationRequired = !app()->environment('local');

        $query = User::where('email', $credentials['email']);
        if (!empty($credentials['company_id'])) {
            $query->where('company_id', $credentials['company_id']);
        } elseif (!empty($credentials['company_domain'])) {
            $query->whereHas('company', function ($q) use ($credentials) {
                $q->where('domain', $credentials['company_domain']);
            });
        }

        $users = $query->get();
        if ($users->count() > 1 && empty($credentials['company_id']) && empty($credentials['company_domain'])) {
            return ['error' => 'Multiple companies found for this email. Provide company_id or company_domain.'];
        }

        $user = $users->first();

        if ($user && Hash::check($credentials['password'], $user->password)) {
            $roleTitle = strtolower(trim((string) ($user->role?->title ?? '')));
            $roleTitle = str_replace([' ', '-', '_'], '', $roleTitle);
            $verificationExempt = in_array($roleTitle, ['superadmin', 'companyadmin', 'admin', 'companyadministrator'], true);

            if ($emailVerificationRequired && !$verificationExempt && is_null($user->email_verified_at)) {
                return ['error' => 'Please verify your email before logging in.'];
            }

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
        $role = Role::whereRaw('LOWER(title) = ?', [strtolower($name)])->first();
        return $role ? $role->id : null;
    }

    public function getUserByEmail($email)
    {
        return User::where('email', $email)->first();
    }

}

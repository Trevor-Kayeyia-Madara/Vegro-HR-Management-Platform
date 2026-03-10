<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function role() { return $this->belongsTo(Role::class); }    

    public function roleTitle(): ?string
    {
        return $this->role?->title;
    }

    public function hasRole($roles): bool
    {
        $roleTitle = strtolower(trim((string) $this->roleTitle()));
        $roleTitle = str_replace([' ', '-', '_'], '', $roleTitle);
        $aliases = [
            'hrmanager' => 'hr',
            'humanresourcesmanager' => 'hr',
        ];
        $roleTitle = $aliases[$roleTitle] ?? $roleTitle;

        $normalizedRoles = array_map(function ($role) use ($aliases) {
            $value = strtolower(trim((string) $role));
            $value = str_replace([' ', '-', '_'], '', $value);
            return $aliases[$value] ?? $value;
        }, is_array($roles) ? $roles : [$roles]);

        if (in_array($roleTitle, ['admin', 'administrator', 'superadmin', 'companyadmin', 'companyadministrator'], true)) {
            return true;
        }

        return in_array($roleTitle, $normalizedRoles, true);
    }

    public function hasPermission(string $key): bool
    {
        $permissionKey = strtolower(trim($key));
        $permissionKey = str_replace([' ', '-', '_'], '', $permissionKey);

        $role = $this->role;
        if (!$role) {
            return false;
        }

        $roleTitle = strtolower(trim((string) $role->title));
        $roleTitle = str_replace([' ', '-', '_'], '', $roleTitle);
        if (in_array($roleTitle, ['admin', 'administrator', 'superadmin', 'companyadmin', 'companyadministrator'], true)) {
            return true;
        }

        if (!$role->relationLoaded('permissions')) {
            $role->load('permissions');
        }

        return $role->permissions->contains(function ($permission) use ($permissionKey) {
            $key = strtolower(trim((string) $permission->key));
            $key = str_replace([' ', '-', '_'], '', $key);
            return $key === $permissionKey;
        });
    }
}

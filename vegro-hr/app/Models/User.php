<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Company;
use App\Models\Concerns\BelongsToCompany;
use App\Notifications\VerifyUserEmailNotification;
use App\Notifications\ResetPasswordLinkNotification;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;
    use BelongsToCompany;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'company_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function role() { return $this->belongsTo(Role::class); }    
    public function employee() { return $this->hasOne(Employee::class); }
    public function notifications() { return $this->hasMany(InAppNotification::class); }
    public function chatConversations()
    {
        return $this->belongsToMany(ChatConversation::class, 'chat_conversation_user', 'user_id', 'conversation_id')
            ->withPivot(['joined_at'])
            ->withTimestamps();
    }
    public function chatMessages() { return $this->hasMany(ChatMessage::class, 'user_id'); }
    public function company() { return $this->belongsTo(Company::class); }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyUserEmailNotification());
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordLinkNotification($token));
    }

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

        if ($roleTitle === 'financemanager') {
            return in_array('financemanager', $normalizedRoles, true)
                || in_array('finance', $normalizedRoles, true)
                || in_array('manager', $normalizedRoles, true);
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

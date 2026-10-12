<?php

namespace App\Services;

use App\Models\LoginLink;
use App\Models\Company;
use App\Models\User;
use App\Mail\CompanyOnboardingInvitation;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class LoginLinkService
{
    /**
     * Generate a login link for a user
     */
    public function generateLoginLink(User $user, int $expiresInHours = 24): LoginLink
    {
        // Delete any existing unused login links for this user
        LoginLink::where('user_id', $user->id)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->delete();

        // Create new login link
        $loginLink = LoginLink::create([
            'user_id' => $user->id,
            'token' => Str::random(64),
            'expires_at' => Carbon::now()->addHours($expiresInHours),
            'used' => false,
        ]);

        return $loginLink;
    }

    /**
     * Validate a login link token
     */
    public function validateLoginLink(string $token): ?LoginLink
    {
        $loginLink = LoginLink::where('token', $token)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        return $loginLink;
    }

    /**
     * Get the login URL for a user
     */
    public function getLoginUrl(User $user, int $expiresInHours = 24): string
    {
        $loginLink = $this->generateLoginLink($user, $expiresInHours);
        $baseUrl = config('app.url');
        
        return "{$baseUrl}/login-link/{$loginLink->token}";
    }

    /**
     * Send login link via email
     */
    public function sendLoginLinkEmail(
        User $user,
        int $expiresInHours = 24,
        ?string $loginUrl = null,
        ?Company $company = null,
        ?string $role = null,
    ): bool
    {
        try {
            Mail::to($user->email)->send(new CompanyOnboardingInvitation(
                user: $user,
                loginUrl: $loginUrl ?? $this->getLoginUrl($user, $expiresInHours),
                expiresInHours: $expiresInHours,
                company: $company,
                role: $role,
            ));

            return true;
        } catch (\Throwable $e) {
            \Log::error('Failed to send login link email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark login link as used
     */
    public function markLinkAsUsed(LoginLink $loginLink): void
    {
        $loginLink->markAsUsed();
    }
}

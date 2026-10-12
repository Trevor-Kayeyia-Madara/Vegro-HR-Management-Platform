<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CompanyOnboardingInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $loginUrl,
        public int $expiresInHours,
        public ?Company $company = null,
        public ?string $role = null,
    ) {
    }

    public function build(): self
    {
        $companyName = $this->company?->name ?? 'Vegro HR';
        $role = $this->role ?? 'team member';
        $details = array_filter([
            'Industry: ' . ($this->company?->industry ?? ''),
            'Country: ' . ($this->company?->country ?? ''),
            'Domain: ' . ($this->company?->domain ?? ''),
            'Plan: ' . ($this->company?->plan ?? ''),
            'Company email: ' . ($this->company?->email ?? ''),
            'Phone: ' . ($this->company?->phone ?? ''),
        ], static fn (string $detail): bool => !str_ends_with($detail, ': '));

        return $this->subject("Welcome to {$companyName} on Vegro HR")
            ->text('emails.company-onboarding-invitation-text', [
                'user' => $this->user,
                'company' => $this->company,
                'role' => $role,
                'details' => $details,
                'loginUrl' => $this->loginUrl,
                'expiresInHours' => $this->expiresInHours,
            ]);
    }
}

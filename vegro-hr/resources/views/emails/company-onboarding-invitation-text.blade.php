Hello {{ $user->name }},

You have been added to {{ $company?->name ?? 'Vegro HR' }} as {{ $role }}.

@if (count($details))
Company onboarding details:
@foreach ($details as $detail)
- {{ $detail }}
@endforeach

@endif
Use this secure link to sign in:
{{ $loginUrl }}

This link expires in {{ $expiresInHours }} hours and can only be used once.

Regards,
Vegro HR

<?php
require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Email Verification Fix Script ===\n\n";

try {
    $updated = \App\Models\User::where('is_super_admin', true)
        ->whereNull('email_verified_at')
        ->update(['email_verified_at' => now()]);

    echo "✓ Updated $updated super admin(s) with email verification\n";

    $updatedUsers = \App\Models\User::whereNull('email_verified_at')
        ->where('created_at', '>=', now()->subDays(1))
        ->update(['email_verified_at' => now()]);

    echo "✓ Updated $updatedUsers user(s) created in last 24 hours\n";
    echo "\n✓ Email verification fix completed successfully!\n";
    echo "\nIMPORTANT: Delete this script after use for security.\n";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

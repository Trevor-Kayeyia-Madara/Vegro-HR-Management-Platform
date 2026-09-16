#!/bin/bash

# Backend Deployment Script for Two-Domain Architecture
# Deploys Laravel backend with login link features

set -e

echo "🚀 Preparing Backend Deployment Package (Two-Domain)"
echo "===================================================="

# Configuration
DOMAIN="vegro-hr.invodtechltd.com"
FRONTEND_DOMAIN="app.vegro-hr.invodtechltd.com"
API_URL="https://$DOMAIN/api"
DB_NAME="invodte1_vegro-hr"
DB_USER="invodte1_vegro-hr"
DB_PASS="KbHCMxNFKJneGwjCVbLS"

echo "📋 Configuration:"
echo "Backend Domain: $DOMAIN"
echo "Frontend Domain: $FRONTEND_DOMAIN"
echo "API URL: $API_URL"
echo "Database: $DB_NAME"
echo ""

# Step 1: Install all Laravel dependencies
echo "📦 Installing all Laravel dependencies..."
composer install
echo "✅ Dependencies installed"
echo ""

# Step 2: Optimize Laravel
echo "⚡ Optimizing Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "✅ Laravel optimized"
echo ""

# Step 3: Create production .env
echo "📝 Creating production .env..."
cat > .env.backend << EOF
APP_NAME="Vegro HR"
APP_ENV=production
APP_KEY=base64:B27ppg8LP4ndqvkhtgYNIrgPMVxlO46LXVWxL4WOA7A=
APP_DEBUG=false
APP_URL=https://$DOMAIN

LOG_CHANNEL=stack
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=$DB_NAME
DB_USERNAME=$DB_USER
DB_PASSWORD=$DB_PASS

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MAIL_MAILER=smtp
MAIL_HOST=localhost
MAIL_PORT=587
MAIL_USERNAME=noreply@$DOMAIN
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@$DOMAIN"
MAIL_FROM_NAME="${APP_NAME}"
EOF
echo "✅ Production .env created"
echo ""

# Step 4: Generate migration SQL
echo "🗄️  Generating migration SQL..."
php artisan migrate --pretend > migration.sql
echo "✅ Migration SQL generated: migration.sql"
echo ""

# Step 5: Create web-based helper scripts
echo "🛠️  Creating web-based helper scripts..."

# Migration runner
cat > public/migrate.php << 'PHPEOF'
<?php
require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    Artisan::call('migrate', ['--force' => true]);
    echo "Migration completed successfully!\n";
    echo Artisan::output();
    
    // Seed roles and permissions
    Artisan::call('db:seed', ['--force' => true]);
    echo "Database seeding completed successfully!\n";
    echo Artisan::output();
} catch (Exception $e) {
    echo "Migration/Seeding failed: " . $e->getMessage() . "\n";
}
PHPEOF

# Key generator
cat > public/generate_key.php << 'PHPEOF'
<?php
require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    Artisan::call('key:generate');
    echo "Application key generated successfully!\n";
    echo Artisan::output();
} catch (Exception $e) {
    echo "Key generation failed: " . $e->getMessage() . "\n";
}
PHPEOF

# Storage copier (alternative to symlink for shared hosting)
cat > public/copy_storage.php << 'PHPEOF'
<?php
/**
 * Copy Storage Files (Alternative to Symlink)
 * This script copies storage files instead of creating a symlink
 * Use this when symlink() is disabled on shared hosting
 */

ini_set('output_buffering', 'off');
ini_set('zlib.output_compression', false);
while (@ob_end_flush());

header('Content-Type: text/plain; charset=utf-8');
header('X-Accel-Buffering: no');

echo "=== Storage Copy Script ===\n\n";

$sourceDir = __DIR__ . '/../storage/app/public';
$targetDir = __DIR__ . '/storage';

echo "Source directory: $sourceDir\n";
echo "Target directory: $targetDir\n\n";

if (!is_dir($sourceDir)) {
    echo "Creating source directory structure...\n";
    if (!mkdir($sourceDir, 0755, true)) {
        echo "ERROR: Failed to create source directory\n";
        exit(1);
    }
    echo "Source directory created\n\n";
}

if (!is_dir($targetDir)) {
    echo "Creating target directory: $targetDir\n";
    if (!mkdir($targetDir, 0755, true)) {
        echo "ERROR: Failed to create target directory\n";
        exit(1);
    }
    echo "Target directory created\n\n";
}

function copyDirectory($source, $target) {
    echo "Copying from $source to $target\n";
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    $fileCount = 0;
    $dirCount = 0;
    
    foreach ($iterator as $item) {
        $relativePath = $iterator->getSubPathName();
        $targetPath = $target . '/' . $relativePath;
        
        if ($item->isDir()) {
            if (!is_dir($targetPath)) {
                if (!mkdir($targetPath, 0755, true)) {
                    echo "ERROR: Failed to create directory: $targetPath\n";
                    return false;
                }
                $dirCount++;
                echo "  Created directory: $relativePath\n";
            }
        } else {
            if (!copy($item->getPathname(), $targetPath)) {
                echo "ERROR: Failed to copy file: $relativePath\n";
                return false;
            }
            $fileCount++;
            echo "  Copied file: $relativePath\n";
        }
    }
    
    echo "Copied $fileCount files and $dirCount directories\n";
    return true;
}

echo "Starting copy operation...\n";
if (copyDirectory($sourceDir, $targetDir)) {
    echo "\n✓ Storage copy completed successfully!\n";
    echo "Files are now available at: public/storage/\n";
    echo "\nIMPORTANT: You can delete this script after successful deployment.\n";
} else {
    echo "\n✗ Storage copy failed.\n";
    echo "Please check file permissions and try again.\n";
    exit(1);
}
PHPEOF

# Cache clearer
cat > public/clear_cache.php << 'PHPEOF'
<?php
require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    Artisan::call('route:clear');
    Artisan::call('config:clear');
    echo "Cache cleared successfully!\n";
} catch (Exception $e) {
    echo "Cache clear failed: " . $e->getMessage() . "\n";
}
PHPEOF

# Email verification fix
cat > public/fix_email_verification.php << 'PHPEOF'
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
PHPEOF

echo "✅ Web-based helper scripts created"
echo ""

# Step 6: Copy .htaccess to public directory
echo "🔧 Copying .htaccess to public directory..."
cp .htaccess.production public/.htaccess
echo "✅ .htaccess copied"
echo ""

# Step 7: Create backend deployment archive
echo "📦 Creating backend deployment archive..."
tar -czf backend-two-domain.tar.gz \
  --exclude='.git' \
  --exclude='.env' \
  --exclude='.env.backend' \
  --exclude='storage/logs/*' \
  --exclude='storage/framework/cache/*' \
  --exclude='storage/framework/sessions/*' \
  --exclude='storage/framework/views/*' \
  --exclude='.kilo' \
  --exclude='.postman' \
  --exclude='postman' \
  --exclude='*.postman_collection.json' \
  --exclude='node_modules' \
  --exclude='vegro-hr-frontend' \
  --exclude='*.md' \
  --exclude='*.MD' \
  --exclude='deploy-*.sh' \
  .
echo "✅ Backend archive created: backend-two-domain.tar.gz"
echo ""

echo "🎯 Backend Deployment Package Ready!"
echo "======================================"
echo ""
echo "📁 Files Created:"
echo "  - backend-two-domain.tar.gz (Laravel backend with login link features)"
echo "  - .env.backend (environment template)"
echo "  - migration.sql (database schema reference)"
echo "  - Web-based helper scripts in public/ directory"
echo ""
echo "📋 Backend Deployment Steps:"
echo "1. Create subdomain: $DOMAIN"
echo "2. Create MySQL database: $DB_NAME"
echo "3. Upload backend-two-domain.tar.gz to DirectAdmin File Manager"
echo "4. Extract in public_html/vegro-hr directory"
echo "5. Rename .env.backend to .env and verify database credentials"
echo "6. Set document root to public_html/vegro-hr/public"
echo "7. Run web-based scripts in this order:"
echo "   - https://$DOMAIN/generate_key.php"
echo "   - https://$DOMAIN/copy_storage.php"
echo "   - https://$DOMAIN/migrate.php"
echo "   - https://$DOMAIN/clear_cache.php"
echo "   - https://$DOMAIN/fix_email_verification.php (if users exist before fix)"
echo "8. Delete the helper scripts after successful deployment"
echo ""
echo "📋 Frontend Deployment:"
echo "1. Run ./deploy-frontend-two-domain.sh"
echo "2. Upload frontend package to app subdomain"
echo ""
echo "📚 Full guide: TWO_DOMAIN_DEPLOYMENT.md"
echo ""
echo "✨ Backend package ready for two-domain deployment!"
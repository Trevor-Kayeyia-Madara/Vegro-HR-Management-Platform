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
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
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

# Storage linker
cat > public/link_storage.php << 'PHPEOF'
<?php
require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    Artisan::call('storage:link');
    echo "Storage linked successfully!\n";
    echo Artisan::output();
} catch (Exception $e) {
    echo "Storage link failed: " . $e->getMessage() . "\n";
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
echo "   - https://$DOMAIN/link_storage.php"
echo "   - https://$DOMAIN/migrate.php"
echo "   - https://$DOMAIN/clear_cache.php"
echo "8. Delete the helper scripts after successful deployment"
echo ""
echo "📋 Frontend Deployment:"
echo "1. Run ./deploy-frontend-two-domain.sh"
echo "2. Upload frontend package to app subdomain"
echo ""
echo "📚 Full guide: TWO_DOMAIN_DEPLOYMENT.md"
echo ""
echo "✨ Backend package ready for two-domain deployment!"
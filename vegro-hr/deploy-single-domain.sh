#!/bin/bash

# Single-Domain Deployment Script
# Deploys both frontend and backend on vegro-hr.invodtechltd.com

set -e

echo "🚀 Preparing Single-Domain Deployment Package"
echo "======================================"

# Configuration
DOMAIN="vegro-hr.invodtechltd.com"
API_URL="https://$DOMAIN/api"
DB_NAME="invodte1_vegro-hr"
DB_USER="invodte1_vegro-hr"
DB_PASS="KbHCMxNFKJneGwjCVbLS"

echo "📋 Configuration:"
echo "Domain: $DOMAIN"
echo "API URL: $API_URL"
echo "Database: $DB_NAME"
echo ""

# Step 1: Build frontend with correct API URL
echo "🔨 Building Vue.js frontend for single-domain..."
cd vegro-hr-frontend
echo "VITE_API_BASE_URL=$API_URL" > .env
npm install
npm run build
cd ..
echo "✅ Frontend built successfully"
echo ""

# Step 2: Install all Laravel dependencies
echo "📦 Installing all Laravel dependencies..."
composer install
echo "✅ Dependencies installed"
echo ""

# Step 3: Optimize Laravel
echo "⚡ Optimizing Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "✅ Laravel optimized"
echo ""

# Step 4: Create production .env
echo "📝 Creating production .env..."
cat > .env.production << EOF
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

# Step 5: Generate migration SQL
echo "🗄️  Generating migration SQL..."
php artisan migrate --pretend > migration.sql
echo "✅ Migration SQL generated: migration.sql"
echo ""

# Step 6: Create web-based helper scripts
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

# Step 7: Copy .htaccess to public directory
echo "🔧 Copying .htaccess to public directory..."
cp .htaccess.production public/.htaccess
echo "✅ .htaccess copied"
echo ""

# Step 8: Create complete deployment archive
echo "📦 Creating complete single-domain deployment archive..."
tar -czf single-domain-deploy.tar.gz \
  --exclude='.git' \
  --exclude='.env' \
  --exclude='.env.production' \
  --exclude='storage/logs/*' \
  --exclude='storage/framework/cache/*' \
  --exclude='storage/framework/sessions/*' \
  --exclude='storage/framework/views/*' \
  --exclude='.kilo' \
  --exclude='.postman' \
  --exclude='postman' \
  --exclude='*.postman_collection.json' \
  --exclude='node_modules' \
  .
echo "✅ Complete archive created: single-domain-deploy.tar.gz"
echo ""

echo "🎯 Single-Domain Deployment Package Ready!"
echo "======================================"
echo ""
echo "📁 Files Created:"
echo "  - single-domain-deploy.tar.gz (backend + frontend together)"
echo "  - .env.production (environment template)"
echo "  - migration.sql (database schema reference)"
echo "  - Web-based helper scripts in public/ directory"
echo ""
echo "📋 Deployment Steps:"
echo "1. Create subdomain: $DOMAIN"
echo "2. Create MySQL database: $DB_NAME"
echo "3. Upload single-domain-deploy.tar.gz to DirectAdmin File Manager"
echo "4. Extract in public_html/vegro-hr directory"
echo "5. Rename .env.production to .env and verify database credentials"
echo "6. Set document root to public_html/vegro-hr/public"
echo "7. Run web-based scripts in this order:"
echo "   - https://$DOMAIN/generate_key.php"
echo "   - https://$DOMAIN/link_storage.php"
echo "   - https://$DOMAIN/migrate.php"
echo "   - https://$DOMAIN/clear_cache.php"
echo "8. Delete the helper scripts after successful deployment"
echo ""
echo "📋 Database Setup:"
echo "1. Go to MySQL Management in DirectAdmin"
echo "2. Create database: $DB_NAME"
echo "3. Create database user with strong password"
echo "4. Grant all privileges to the user"
echo "5. Update credentials in .env if different from script"
echo ""
echo "📚 Full guide: COMPLETE_FRESH_DEPLOYMENT.md"
echo ""
echo "✨ Single-domain package ready for deployment!"
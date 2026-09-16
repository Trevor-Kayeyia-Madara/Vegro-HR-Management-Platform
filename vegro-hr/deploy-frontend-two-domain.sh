#!/bin/bash

# Frontend Deployment Script for Two-Domain Architecture
# Deploys Vue.js frontend to separate domain

set -e

echo "🚀 Preparing Frontend Deployment for Two-Domain Architecture"
echo "=============================================================="

# Configuration
API_DOMAIN="https://vegro-hr.invodtechltd.com/api"
FRONTEND_DOMAIN="app.vegro-hr.invodtechltd.com"

echo "📋 Configuration:"
echo "API Domain: $API_DOMAIN"
echo "Frontend Domain: $FRONTEND_DOMAIN"
echo ""

# Step 1: Build frontend with correct API URL
echo "🔨 Building Vue.js frontend for production..."
cd vegro-hr-frontend
echo "VITE_API_BASE_URL=$API_DOMAIN" > .env
npm install
npm run build
cd ..
echo "✅ Frontend built successfully"
echo ""

# Step 2: Create frontend deployment package
echo "📦 Creating frontend deployment package..."
cd vegro-hr-frontend/dist
tar -czf ../../frontend-two-domain.tar.gz .
cd ../..
echo "✅ Frontend archive created: frontend-two-domain.tar.gz"
echo ""

# Step 3: Create .htaccess for frontend
echo "🔧 Creating .htaccess for frontend..."
cat > frontend-htaccess << 'EOF'
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Handle Vue.js Router
    RewriteBase /
    RewriteRule ^index\.html$ - [L]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule . /index.html [L]
</IfModule>

# Security Headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>

# Enable Gzip Compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>

# Cache Static Assets
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
</IfModule>

# Disable Directory Browsing
Options -Indexes
EOF
echo "✅ Frontend .htaccess created"
echo ""

echo "🎯 Frontend Deployment Package Ready!"
echo "======================================"
echo ""
echo "📁 Files Created:"
echo "  - frontend-two-domain.tar.gz (frontend build files)"
echo "  - frontend-htaccess (Apache configuration)"
echo ""
echo "📋 Frontend Deployment Steps:"
echo "1. Create subdomain: $FRONTEND_DOMAIN"
echo "2. Upload frontend-two-domain.tar.gz to DirectAdmin File Manager"
echo "3. Extract in public_html/app directory"
echo "4. Rename frontend-htaccess to .htaccess"
echo "5. Set document root to public_html/app"
echo "6. Test at: https://$FRONTEND_DOMAIN"
echo ""
echo "📋 Backend Steps:"
echo "1. Run ./deploy-backend-two-domain.sh"
echo "2. Upload backend package to vegro-hr subdomain"
echo ""
echo "✨ Frontend package ready for two-domain deployment!"
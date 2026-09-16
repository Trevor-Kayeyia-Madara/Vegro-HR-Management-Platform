# Vegro HR Platform - Deployment Instructions

## Quick Setup

1. **Rename .env.backend to .env**
2. **Update database credentials in .env**
3. **Set file permissions:** storage/ and bootstrap/cache/ to 777
4. **Set document root to public/ directory**
5. **Run setup scripts:**
   - generate_key.php
   - link_storage.php
   - migrate.php
   - clear_cache.php
6. **Delete helper scripts after setup**

## Configuration

### Database Settings
Update these in .env:
- DB_DATABASE=your_database_name
- DB_USERNAME=your_database_user
- DB_PASSWORD=your_database_password

### App Settings
- APP_URL=https://your-domain.com

## API Endpoints

### Super Admin
- POST /api/super-admin/create - Create super admin
- POST /api/super-admin/onboard-company - Onboard company (requires auth)
- GET /api/super-admin/companies - Get all companies (requires auth)

### Login Links
- POST /api/auth/login-link/send - Send login link
- POST /api/auth/login-link/generate - Generate login link (requires auth)
- GET /login-link/{token} - Login with link

### Authentication
- POST /api/auth/login - Login
- POST /api/auth/register - Register
- POST /api/auth/logout - Logout (requires auth)
- GET /api/auth/me - Get current user (requires auth)

## Support
For detailed documentation, refer to the project repository.
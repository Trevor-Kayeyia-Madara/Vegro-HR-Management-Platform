# 🚀 Two-Domain Deployment Guide with Login Link Features

## 📋 Architecture Overview
- **Backend API:** `vegro-hr.invodtechltd.com` (Laravel with login link features)
- **Frontend App:** `app.vegro-hr.invodtechltd.com` (Vue.js interface)
- **Database:** `invodte1_vegro-hr`
- **New Features:** Super admin, company onboarding, login links

---

## 🎯 New Features

### 1. Super Admin System
- Platform-level administration
- Can onboard new companies
- Creates company admin and HR accounts automatically

### 2. Company Onboarding
- One API call to create company + admin + HR
- Automatic login link generation for both accounts
- Email-based onboarding workflow

### 3. Login Link System
- Secure, time-limited (24 hours) login links
- One-time use for security
- No password required for first login
- Automatic login link sending to employees

### 4. Employee Login Links
- When HR creates employee with email, login link is automatically sent
- Seamless employee onboarding
- Login URL returned in API response for testing

---

## 📦 Deployment Packages

### Backend Package
- **File:** `backend-two-domain.tar.gz`
- **Includes:** Laravel backend with all new features
- **Migrations:** login_links table, is_super_admin field

### Frontend Package
- **File:** `frontend-two-domain.tar.gz`
- **Includes:** Vue.js frontend built for production
- **API URL:** Configured to `https://vegro-hr.invodtechltd.com/api`

---

## 🚀 PART 1: Backend Deployment

### Step 1: Create Backend Subdomain
1. Log in to DirectAdmin
2. Go to **"Subdomains"**
3. Create subdomain: `vegro-hr`
4. Document root: `public_html/vegro-hr`

### Step 2: Configure PHP
1. Go to **"Select PHP Version"**
2. Set PHP version to **8.1+**
3. Enable: mbstring, pdo_mysql, openssl, ctype, json, tokenizer, xml

### Step 3: Create Database
1. Go to **"MySQL Management"**
2. Create database: `invodte1_vegro-hr`
3. Create user: `invodte1_vegro-hr`
4. Set strong password
5. Grant all privileges

### Step 4: Upload Backend
1. Go to **"File Manager"**
2. Navigate to `public_html/vegro-hr`
3. Upload `backend-two-domain.tar.gz`
4. Extract the archive

### Step 5: Configure Environment
1. Rename `.env.backend` to `.env`
2. Edit `.env` and verify:
```env
DB_DATABASE=invodte1_vegro-hr
DB_USERNAME=invodte1_vegro-hr
DB_PASSWORD=KbHCMxNFKJneGwjCVbLS
APP_URL=https://vegro-hr.invodtechltd.com
```

### Step 6: Set Permissions
1. `storage/` folder → 777
2. `bootstrap/cache/` folder → 777
3. Create subdirectories in `storage/`:
   - `framework/cache`
   - `framework/sessions`
   - `framework/views`
   - `logs`

### Step 7: Configure Document Root
1. Go to **"Subdomains"**
2. Find `vegro-hr` subdomain
3. Set document root to: `public_html/vegro-hr/public`

### Step 8: Run Setup Scripts
Visit in order (delete each after use):
1. `https://vegro-hr.invodtechltd.com/generate_key.php`
2. `https://vegro-hr.invodtechltd.com/link_storage.php`
3. `https://vegro-hr.invodtechltd.com/migrate.php`
4. `https://vegro-hr.invodtechltd.com/clear_cache.php`

### Step 9: Cleanup
Delete helper scripts from `public/`

### Step 10: Enable SSL
Enable Let's Encrypt SSL for `vegro-hr.invodtechltd.com`

### Step 11: Test Backend
Visit: `https://vegro-hr.invodtechltd.com/`

Should see:
```json
{
  "message": "Vegro HR API",
  "version": "1.0.0",
  "frontend": "https://app.vegro-hr.invodtechltd.com",
  "api_endpoints": {...}
}
```

---

## 🚀 PART 2: Frontend Deployment

### Step 1: Create Frontend Subdomain
1. Go to **"Subdomains"**
2. Create subdomain: `app`
3. Document root: `public_html/app`

### Step 2: Upload Frontend
1. Go to **"File Manager"**
2. Navigate to `public_html/app`
3. Upload `frontend-two-domain.tar.gz`
4. Extract the archive

### Step 3: Configure Frontend
1. Rename `frontend-htaccess` to `.htaccess`
2. Set document root to `public_html/app`

### Step 4: Enable SSL
Enable Let's Encrypt SSL for `app.vegro-hr.invodtechltd.com`

### Step 5: Test Frontend
Visit: `https://app.vegro-hr.invodtechltd.com`

Should see Vue.js HR Management login page.

---

## 🧪 PART 3: Testing New Features

### Test 1: Create Super Admin
```bash
curl -X POST https://vegro-hr.invodtechltd.com/api/super-admin/create \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Super Admin",
    "email": "superadmin@example.com",
    "password": "secure_password"
  }'
```

### Test 2: Onboard Company
```bash
curl -X POST https://vegro-hr.invodtechltd.com/api/super-admin/onboard-company \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_SUPER_ADMIN_TOKEN" \
  -d '{
    "company_name": "Test Company",
    "company_email": "info@test.com",
    "company_phone": "+254700000000",
    "admin_name": "Company Admin",
    "admin_email": "admin@test.com",
    "hr_name": "HR Manager",
    "hr_email": "hr@test.com"
  }'
```

Copy the login URLs from the response for testing.

### Test 3: Test Login Links
1. Visit the admin login URL
2. Verify you're logged in as admin
3. Visit the HR login URL
4. Verify you're logged in as HR

### Test 4: Create Employee with Login Link
1. Login as HR via frontend
2. Create employee with email
3. Note the login URL in response
4. Test the login link
5. Verify employee can login

---

## 📧 Email Configuration

For automatic email sending, configure in `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@vegro-hr.invodtechltd.com
MAIL_FROM_NAME="Vegro HR"
```

**Note:** Email sending is currently prepared but needs actual implementation in `LoginLinkService.php`. For now, login URLs are returned in API responses for testing.

---

## 🔒 Security Considerations

### Login Link Security:
- ✅ 64-character random tokens
- ✅ 24-hour expiration
- ✅ One-time use only
- ✅ Automatic cleanup of old links
- ✅ HTTPS required

### Super Admin Security:
- ✅ Separate authentication
- ✅ Role-based access control
- ✅ Audit trail for onboarding
- ✅ No automatic super admin creation

---

## 📋 API Endpoints

### Super Admin:
- `POST /api/super-admin/create` - Create super admin
- `POST /api/super-admin/onboard-company` - Onboard company
- `GET /api/super-admin/companies` - Get all companies

### Login Links:
- `POST /api/auth/login-link/send` - Send login link
- `POST /api/auth/login-link/generate` - Generate login link
- `GET /login-link/{token}` - Login with link

---

## 🎯 Workflow Summary

### Super Admin → Company Onboarding:
1. Super admin logs in
2. Onboards company with admin & HR
3. System sends login links to both
4. Admin and HR use links to access system
5. Set passwords after first login

### HR → Employee Onboarding:
1. HR logs in
2. Creates employee with email
3. System sends login link to employee
4. Employee uses link to access system
5. Sets password after first login

---

## 🔄 Maintenance

### Backend Updates:
1. Make changes locally
2. Run `composer install --optimize-autoloader --no-dev`
3. Create new backend package
4. Upload and extract
5. Clear cache

### Frontend Updates:
1. Make changes locally
2. Run `./deploy-frontend-two-domain.sh`
3. Upload new frontend package
4. Extract and replace files

This two-domain architecture provides clean separation, better performance, and easier maintenance for your HR Management Platform with advanced login link features!
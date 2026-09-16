# 🚀 Complete Deployment Guide - Two-Domain Architecture with Login Link Features

## 📋 Quick Overview
- **Backend API:** `vegro-hr.invodtechltd.com` (Laravel)
- **Frontend App:** `app.vegro-hr.invodtechltd.com` (Vue.js)
- **Database:** `invodte1_vegro-hr`
- **New Features:** Super admin, company onboarding, login links

---

## 🎯 Deployment Flow (Sequential)

### Phase 1: DirectAdmin Setup
1. Create backend subdomain
2. Create frontend subdomain
3. Create MySQL database
4. Configure PHP settings

### Phase 2: Backend Deployment
1. Upload backend package
2. Configure environment
3. Set file permissions
4. Run setup scripts
5. Test backend API

### Phase 3: Frontend Deployment
1. Upload frontend package
2. Configure frontend
3. Test frontend loading

### Phase 4: Feature Testing
1. Create super admin
2. Onboard company
3. Test login links
4. Test employee creation

---

## 📦 Deployment Packages

You have two packages ready for upload:
- `backend-two-domain.tar.gz` - Laravel backend with login link features
- `frontend-two-domain.tar.gz` - Vue.js frontend

---

## 🚀 PHASE 1: DirectAdmin Setup

### Step 1.1: Create Backend Subdomain
1. Log in to DirectAdmin
2. Go to **"Subdomains"**
3. Click **"Create Subdomain"**
4. Subdomain name: `vegro-hr`
5. Document root: `public_html/vegro-hr`
6. Click **"Create"**

### Step 1.2: Create Frontend Subdomain
1. Stay in **"Subdomains"**
2. Click **"Create Subdomain"**
3. Subdomain name: `app`
4. Document root: `public_html/app`
5. Click **"Create"**

### Step 1.3: Configure PHP Version
1. Go to **"Select PHP Version"**
2. Select PHP version **8.1 or higher**
3. Click **"Set as default"**
4. Ensure these extensions are enabled:
   - mbstring
   - pdo_mysql
   - openssl
   - ctype
   - json
   - tokenizer
   - xml

### Step 1.4: Create MySQL Database
1. Go to **"MySQL Management"**
2. Click **"Create Database"**
3. Database name: `invodte1_vegro-hr`
4. Click **"Create"**
5. Click **"Create User"**
6. Username: `invodte1_vegro-hr`
7. Password: Generate a strong password (save it!)
8. Click **"Create"**
9. Select the database and user
10. Click **"Add User to Database"**
11. Check **"ALL PRIVILEGES"**
12. Click **"Save"**

**✅ Phase 1 Complete: DirectAdmin configured**

---

## 🚀 PHASE 2: Backend Deployment

### Step 2.1: Upload Backend Package
1. Go to **"File Manager"**
2. Navigate to `public_html/vegro-hr`
3. Click **"Upload"**
4. Select `backend-two-domain.tar.gz` from your computer
5. Click **"Upload"**
6. Wait for upload to complete

### Step 2.2: Extract Backend Files
1. Right-click on `backend-two-domain.tar.gz`
2. Select **"Extract"**
3. Click **"Extract"**
4. Verify files are extracted (you should see `app/`, `config/`, `public/`, etc.)

### Step 2.3: Configure Environment
1. Locate `.env.backend` file in the root
2. Right-click and select **"Rename"**
3. Rename to `.env`
4. Right-click on `.env` and select **"Edit"**
5. Verify these settings:
```env
APP_NAME="Vegro HR"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://vegro-hr.invodtechltd.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=invodte1_vegro-hr
DB_USERNAME=invodte1_vegro-hr
DB_PASSWORD=KbHCMxNFKJneGwjCVbLS
```
6. If you used different database credentials, update them here
7. Click **"Save"**

### Step 2.4: Set File Permissions
1. Select the `storage` folder
2. Click **"Change Permissions"**
3. Set to **777**
4. Click **"Save"**
5. Select the `bootstrap/cache` folder
6. Click **"Change Permissions"**
7. Set to **777**
8. Click **"Save"**

### Step 2.5: Create Required Directories
1. Navigate to `storage/`
2. Create these folders if they don't exist:
   - `framework/cache`
   - `framework/sessions`
   - `framework/views`
   - `logs`
3. Set permissions for each to **777**

### Step 2.6: Configure Document Root
1. Go to **"Subdomains"**
2. Find your `vegro-hr` subdomain
3. Click **"Manage"**
4. Set document root to: `public_html/vegro-hr/public`
5. Click **"Save"**

### Step 2.7: Run Setup Scripts (In Order)

**Script 1: Generate Application Key**
- Visit: `https://vegro-hr.invodtechltd.com/generate_key.php`
- If you see "Application key generated successfully!", proceed
- Go to File Manager → `public/` → Delete `generate_key.php`

**Script 2: Link Storage**
- Visit: `https://vegro-hr.invodtechltd.com/link_storage.php`
- If you see "Storage linked successfully!", proceed
- Go to File Manager → `public/` → Delete `link_storage.php`
- **If this fails:** Create a folder named `storage` in `public/` and set permissions to 777

**Script 3: Run Database Migrations**
- Visit: `https://vegro-hr.invodtechltd.com/migrate.php`
- If you see "Migration completed successfully!", proceed
- Go to File Manager → `public/` → Delete `migrate.php`
- This creates all database tables including login_links

**Script 4: Clear Cache**
- Visit: `https://vegro-hr.invodtechltd.com/clear_cache.php`
- If you see "Cache cleared successfully!", proceed
- Go to File Manager → `public/` → Delete `clear_cache.php`

### Step 2.8: Enable SSL
1. Go to **"SSL Certificates"**
2. Find your `vegro-hr` subdomain
3. Click **"Free & Auto"** (Let's Encrypt)
4. Click **"Save"**

### Step 2.9: Test Backend API
- Visit: `https://vegro-hr.invodtechltd.com/`
- You should see this JSON response:
```json
{
  "message": "Vegro HR API",
  "version": "1.0.0",
  "frontend": "https://app.vegro-hr.invodtechltd.com",
  "api_endpoints": {
    "departments": "https://vegro-hr.invodtechltd.com/api/departments",
    "employees": "https://vegro-hr.invodtechltd.com/api/employees",
    "payrolls": "https://vegro-hr.invodtechltd.com/api/payrolls",
    "payslips": "https://vegro-hr.invodtechltd.com/api/payslips",
    "attendances": "https://vegro-hr.invodtechltd.com/api/attendances",
    "leave-requests": "https://vegro-hr.invodtechltd.com/api/leave-requests",
    "auth": {
      "login": "https://vegro-hr.invodtechltd.com/api/auth/login",
      "register": "https://vegro-hr.invodtechltd.com/api/auth/register",
      "logout": "https://vegro-hr.invodtechltd.com/api/auth/logout",
      "me": "https://vegro-hr.invodtechltd.com/api/auth/me",
      "check": "https://vegro-hr.invodtechltd.com/api/auth/check"
    }
  }
}
```

**✅ Phase 2 Complete: Backend deployed and working**

---

## 🚀 PHASE 3: Frontend Deployment

### Step 3.1: Upload Frontend Package
1. Go to **"File Manager"**
2. Navigate to `public_html/app`
3. Click **"Upload"**
4. Select `frontend-two-domain.tar.gz` from your computer
5. Click **"Upload"**
6. Wait for upload to complete

### Step 3.2: Extract Frontend Files
1. Right-click on `frontend-two-domain.tar.gz`
2. Select **"Extract"**
3. Click **"Extract"**
4. You should see `index.html` and `assets/` folder

### Step 3.3: Configure Frontend
1. Locate `frontend-htaccess` file
2. Right-click and select **"Rename"**
3. Rename to `.htaccess`
4. Click **"Rename"**

### Step 3.4: Configure Document Root
1. Go to **"Subdomains"**
2. Find your `app` subdomain
3. Click **"Manage"**
4. Set document root to: `public_html/app`
5. Click **"Save"**

### Step 3.5: Enable SSL
1. Go to **"SSL Certificates"**
2. Find your `app` subdomain
3. Click **"Free & Auto"** (Let's Encrypt)
4. Click **"Save"**

### Step 3.6: Test Frontend
- Visit: `https://app.vegro-hr.invodtechltd.com`
- You should see the Vue.js HR Management login page
- Dark-themed interface with "Vegro HR" branding

**✅ Phase 3 Complete: Frontend deployed and working**

---

## 🚀 PHASE 4: Feature Testing

### Step 4.1: Create Super Admin
Use API to create the first super admin:

```bash
curl -X POST https://vegro-hr.invodtechltd.com/api/super-admin/create \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Super Admin",
    "email": "superadmin@example.com",
    "password": "SecurePassword123!"
  }'
```

**Expected Response:**
```json
{
  "message": "Super admin created successfully",
  "user": {
    "id": 1,
    "name": "Super Admin",
    "email": "superadmin@example.com",
    "is_super_admin": true
  }
}
```

### Step 4.2: Login as Super Admin
Use the super admin credentials to get an auth token:

```bash
curl -X POST https://vegro-hr.invodtechltd.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "superadmin@example.com",
    "password": "SecurePassword123!"
  }'
```

**Save the token from the response** - you'll need it for the next step.

### Step 4.3: Onboard First Company
Use the super admin token to onboard a company:

```bash
curl -X POST https://vegro-hr.invodtechltd.com/api/super-admin/onboard-company \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_SUPER_ADMIN_TOKEN" \
  -d '{
    "company_name": "Security Firm Ltd",
    "company_email": "info@securityfirm.com",
    "company_phone": "+254700000000",
    "admin_name": "Company Admin",
    "admin_email": "admin@securityfirm.com",
    "hr_name": "HR Manager",
    "hr_email": "hr@securityfirm.com"
  }'
```

**Expected Response:**
```json
{
  "message": "Company onboarded successfully",
  "company": {
    "id": 1,
    "name": "Security Firm Ltd",
    "email": "info@securityfirm.com",
    "phone": "+254700000000",
    "status": "active"
  },
  "admin": {
    "user": {
      "id": 2,
      "name": "Company Admin",
      "email": "admin@securityfirm.com"
    },
    "login_url": "https://vegro-hr.invodtechltd.com/login-link/abc123...",
    "expires_in_hours": 24
  },
  "hr": {
    "user": {
      "id": 3,
      "name": "HR Manager",
      "email": "hr@securityfirm.com"
    },
    "login_url": "https://vegro-hr.invodtechltd.com/login-link/xyz789...",
    "expires_in_hours": 24
  }
}
```

**Copy the login URLs** - you'll use them to test.

### Step 4.4: Test Admin Login Link
1. Visit the admin login URL from the response
2. You should be automatically logged in as the company admin
3. Note the auth token for future requests

### Step 4.5: Test HR Login Link
1. Visit the HR login URL from the response
2. You should be automatically logged in as the HR manager
3. Note the auth token for future requests

### Step 4.6: Test Employee Creation with Login Link
Use the HR token to create an employee:

```bash
curl -X POST https://vegro-hr.invodtechltd.com/api/employees \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_HR_TOKEN" \
  -d '{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john.doe@securityfirm.com",
    "position": "Security Guard",
    "department_id": 1,
    "salary": 50000
  }'
```

**Expected Response:**
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john.doe@securityfirm.com",
  "employee_number": "EMP2026091612345",
  "login_url": "https://vegro-hr.invodtechltd.com/login-link/newtoken...",
  "user_id": 4
}
```

### Step 4.7: Test Employee Login Link
1. Visit the employee login URL from the response
2. You should be automatically logged in as the employee
3. The employee can then set their password

**✅ Phase 4 Complete: All features tested and working**

---

## 🎯 Quick Checklist

### Phase 1: DirectAdmin Setup
- [ ] Backend subdomain created: vegro-hr
- [ ] Frontend subdomain created: app
- [ ] Database created: invodte1_vegro-hr
- [ ] PHP version set to 8.1+
- [ ] Required PHP extensions enabled

### Phase 2: Backend Deployment
- [ ] Backend package uploaded and extracted
- [ ] .env configured with database credentials
- [ ] File permissions set (storage = 777, bootstrap/cache = 777)
- [ ] Required directories created in storage/
- [ ] Document root set to public_html/vegro-hr/public
- [ ] Setup scripts run successfully (generate_key, link_storage, migrate, clear_cache)
- [ ] Helper scripts deleted
- [ ] SSL enabled
- [ ] Backend API responding correctly

### Phase 3: Frontend Deployment
- [ ] Frontend package uploaded and extracted
- [ ] .htaccess configured
- [ ] Document root set to public_html/app
- [ ] SSL enabled
- [ ] Frontend loading correctly

### Phase 4: Feature Testing
- [ ] Super admin created successfully
- [ ] Super admin can login
- [ ] Company onboarded successfully
- [ ] Admin login link works
- [ ] HR login link works
- [ ] Employee creation works
- [ ] Employee login link works

---

## 🔧 Troubleshooting

### Backend Issues

**500 Internal Server Error:**
- Check PHP version is 8.1+
- Verify file permissions (storage = 777)
- Check if `.env` file exists
- Verify database credentials in `.env`
- Check error logs: `storage/logs/laravel.log`

**Database Connection Error:**
- Verify database exists in MySQL Management
- Check database credentials in `.env`
- Ensure database user has all privileges
- Test database connection via phpMyAdmin

**Migration Failed:**
- Ensure database exists and is empty
- Check database credentials in `.env`
- Verify PHP MySQL extensions are enabled
- Check if database user has CREATE TABLE permissions

**Helper Scripts Failed:**
- Check if script files exist in `public/`
- Verify PHP can execute scripts
- Check file permissions on helper scripts
- Note error messages for specific issues

### Frontend Issues

**Frontend Not Loading:**
- Check if files were extracted correctly
- Verify document root points to `public_html/app`
- Check if `.htaccess` exists and is named correctly
- Ensure SSL is working
- Clear browser cache

**API Connection Issues:**
- Verify backend API is accessible
- Check CORS configuration includes frontend domain
- Test API endpoints directly
- Check browser console for CORS errors
- Verify APP_URL in backend `.env` is correct

---

## 📧 Email Configuration (Optional)

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

**Note:** Email sending is prepared but needs implementation in `LoginLinkService.php`. For now, login URLs are returned in API responses for testing.

---

## 🔄 Maintenance

### Backend Updates:
1. Make changes locally
2. Run `composer install --optimize-autoloader --no-dev`
3. Run `./deploy-backend-two-domain.sh`
4. Upload new backend package
5. Extract and replace files
6. Clear cache

### Frontend Updates:
1. Make changes locally
2. Run `./deploy-frontend-two-domain.sh`
3. Upload new frontend package
4. Extract and replace files
5. Clear browser cache

---

## 🎯 Success Criteria

Your deployment is successful when:
1. ✅ Backend API responds with JSON at `https://vegro-hr.invodtechltd.com/`
2. ✅ Frontend loads at `https://app.vegro-hr.invodtechltd.com`
3. ✅ Super admin can be created and login
4. ✅ Company onboarding works with login links
5. ✅ Admin and HR can use login links to access system
6. ✅ HR can create employees with automatic login links
7. ✅ Employees can use login links to access system
8. ✅ SSL is enabled for both domains

This step-by-step guide provides a clear, sequential flow for deploying your HR Management Platform with advanced login link features!
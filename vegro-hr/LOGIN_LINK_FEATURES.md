# 🚀 Login Link & Super Admin Features Guide

## 🎯 New Features Overview

This guide covers the new single-domain deployment with advanced features:

1. **Single-Domain Architecture** - Frontend and backend on `vegro-hr.invodtechltd.com`
2. **Super Admin System** - Platform-level administration
3. **Company Onboarding** - Complete company setup with admin and HR accounts
4. **Login Link System** - Email-based login links for secure access
5. **Employee Login Links** - Automatic login link sending when HR creates employees

---

## 📦 Deployment Package

### Files Ready for Upload:
- **single-domain-deploy.tar.gz** - Complete Laravel + Vue.js application with new features
- Database migrations included (login_links table, is_super_admin field)

---

## 🔐 Feature 1: Super Admin System

### What It Does:
- Platform administrator with system-wide access
- Can onboard new companies
- Can create company admin and HR accounts
- Can manage all companies

### How to Create Super Admin:

**API Endpoint:**
```
POST /api/super-admin/create
```

**Request Body:**
```json
{
  "name": "Super Admin Name",
  "email": "superadmin@example.com",
  "password": "secure_password"
}
```

**Response:**
```json
{
  "message": "Super admin created successfully",
  "user": {
    "id": 1,
    "name": "Super Admin Name",
    "email": "superadmin@example.com",
    "is_super_admin": true
  }
}
```

---

## 🏢 Feature 2: Company Onboarding

### What It Does:
- Super admin can onboard new companies
- Automatically creates company admin account
- Automatically creates HR account
- Sends login links to both accounts

### How to Onboard a Company:

**API Endpoint:**
```
POST /api/super-admin/onboard-company
```

**Request Body:**
```json
{
  "company_name": "Example Company Ltd",
  "company_email": "info@example.com",
  "company_phone": "+254700000000",
  "admin_name": "John Admin",
  "admin_email": "admin@example.com",
  "hr_name": "Jane HR",
  "hr_email": "hr@example.com"
}
```

**Response:**
```json
{
  "message": "Company onboarded successfully",
  "company": {
    "id": 1,
    "name": "Example Company Ltd",
    "email": "info@example.com",
    "phone": "+254700000000",
    "status": "active"
  },
  "admin": {
    "user": {
      "id": 2,
      "name": "John Admin",
      "email": "admin@example.com"
    },
    "login_url": "https://vegro-hr.invodtechltd.com/login-link/abc123...",
    "expires_in_hours": 24
  },
  "hr": {
    "user": {
      "id": 3,
      "name": "Jane HR",
      "email": "hr@example.com"
    },
    "login_url": "https://vegro-hr.invodtechltd.com/login-link/xyz789...",
    "expires_in_hours": 24
  }
}
```

### What Happens:
1. Company is created in database
2. Company admin user is created with admin role
3. HR user is created with HR role
4. Login links are generated for both users
5. Login links are sent via email (if email is configured)
6. Login URLs are returned in response for testing

---

## 🔗 Feature 3: Login Link System

### What It Does:
- Secure, time-limited login links
- No password required for first-time login
- Automatic expiration after 24 hours
- One-time use (link becomes invalid after use)

### How Login Links Work:

**Generate Login Link:**
```
POST /api/auth/login-link/generate
```

**Request Body:**
```json
{
  "user_id": 2
}
```

**Response:**
```json
{
  "message": "Login link generated",
  "login_url": "https://vegro-hr.invodtechltd.com/login-link/abc123...",
  "user": {
    "id": 2,
    "name": "John Admin",
    "email": "admin@example.com"
  },
  "expires_in_hours": 24
}
```

**Use Login Link:**
```
GET /login-link/{token}
```

**Response:**
```json
{
  "message": "Login successful",
  "user": {
    "id": 2,
    "name": "John Admin",
    "email": "admin@example.com"
  },
  "token": "auth_token_here"
}
```

### Login Link Security:
- ✅ Tokens are 64-character random strings
- ✅ Links expire after 24 hours
- ✅ Links are one-time use
- ✅ Old unused links are automatically deleted when new ones are generated
- ✅ Used links are marked and cannot be reused

---

## 👤 Feature 4: Employee Login Links

### What It Does:
- When HR creates an employee with an email, a login link is automatically sent
- Employee can login without setting a password initially
- Seamless onboarding experience

### How It Works:

**HR Creates Employee:**
```
POST /api/employees
```

**Request Body:**
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john.doe@example.com",
  "position": "Security Guard",
  "department_id": 1,
  "salary": 50000
}
```

**Response:**
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john.doe@example.com",
  "employee_number": "EMP2026091612345",
  "login_url": "https://vegro-hr.invodtechltd.com/login-link/xyz789...",
  "user_id": 4
}
```

### What Happens:
1. Employee record is created
2. User account is automatically created with random password
3. Employee role is assigned to the user
4. Login link is generated
5. Login link is sent via email to employee
6. Login URL is returned in response for testing

---

## 📋 API Endpoints Summary

### Super Admin Routes:
- `POST /api/super-admin/create` - Create super admin account
- `POST /api/super-admin/onboard-company` - Onboard new company with admin & HR
- `GET /api/super-admin/companies` - Get all companies

### Login Link Routes:
- `POST /api/auth/login-link/send` - Send login link to user email
- `POST /api/auth/login-link/generate` - Generate login link for user (admin)
- `GET /login-link/{token}` - Login using login link token

---

## 🚀 Deployment Instructions

### Step 1: Upload Single-Domain Package
1. Upload `single-domain-deploy.tar.gz` to DirectAdmin File Manager
2. Extract in `public_html/vegro-hr` directory
3. Rename `.env.production` to `.env`
4. Set document root to `public_html/vegro-hr/public`

### Step 2: Run Setup Scripts
Visit these URLs in order:
- `https://vegro-hr.invodtechltd.com/generate_key.php`
- `https://vegro-hr.invodtechltd.com/link_storage.php`
- `https://vegro-hr.invodtechltd.com/migrate.php`
- `https://vegro-hr.invodtechltd.com/clear_cache.php`

### Step 3: Delete Helper Scripts
Remove all helper PHP scripts from `public/` directory

### Step 4: Create Super Admin
Use the API to create your first super admin:
```bash
curl -X POST https://vegro-hr.invodtechltd.com/api/super-admin/create \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Your Name",
    "email": "your@email.com",
    "password": "secure_password"
  }'
```

### Step 5: Onboard First Company
Login as super admin and onboard your first company:
```bash
curl -X POST https://vegro-hr.invodtechltd.com/api/super-admin/onboard-company \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_SUPER_ADMIN_TOKEN" \
  -d '{
    "company_name": "Your Company",
    "company_email": "info@yourcompany.com",
    "company_phone": "+254700000000",
    "admin_name": "Company Admin",
    "admin_email": "admin@yourcompany.com",
    "hr_name": "HR Manager",
    "hr_email": "hr@yourcompany.com"
  }'
```

---

## 🧪 Testing the Features

### Test 1: Create Super Admin
1. Visit the API endpoint to create super admin
2. Verify the user is created with `is_super_admin: true`
3. Login with the super admin credentials

### Test 2: Onboard Company
1. Use super admin token to call onboarding endpoint
2. Verify company is created
3. Verify admin and HR users are created
4. Copy the login URLs for testing

### Test 3: Test Login Links
1. Visit the admin login URL
2. Verify you're logged in as admin
3. Visit the HR login URL
4. Verify you're logged in as HR

### Test 4: Create Employee with Login Link
1. Login as HR
2. Create a new employee with email
3. Note the login URL in response
4. Test the login link
5. Verify employee can login

---

## 📧 Email Configuration

For login links to be sent via email, configure mail settings in `.env`:

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

### Email Templates (TODO):
Currently, the system generates login URLs but email sending needs to be implemented. You can:

1. Configure Laravel mail settings
2. Create email templates for login links
3. Implement the `sendLoginLinkEmail` method in `LoginLinkService.php`

For now, login URLs are returned in API responses for testing and manual sharing.

---

## 🔒 Security Considerations

### Login Link Security:
- ✅ Tokens are cryptographically secure
- ✅ Links expire after 24 hours
- ✅ One-time use only
- ✅ Automatic cleanup of old links
- ✅ HTTPS required for production

### Super Admin Security:
- ✅ Separate authentication flow
- ✅ Role-based access control
- ✅ Audit trail for company onboarding
- ✅ No automatic super admin creation

---

## 📋 Database Changes

### New Tables:
- `login_links` - Stores login link tokens and metadata

### New Columns:
- `users.is_super_admin` - Boolean flag for super admin users

### Migrations:
- `2026_09_16_120000_create_login_links_table.php`
- `2026_09_16_120001_add_is_super_admin_to_users_table.php`

---

## 🎯 Workflow Summary

### Super Admin Workflow:
1. Super admin logs in
2. Super admin onboards new company
3. System creates company + admin + HR accounts
4. System sends login links to admin and HR
5. Admin and HR use login links to access system
6. Admin and HR set their passwords after first login

### HR Workflow:
1. HR logs in
2. HR creates new employee with email
3. System creates employee user account
4. System sends login link to employee
5. Employee uses login link to access system
6. Employee sets password after first login

This creates a seamless, secure onboarding experience for all users!
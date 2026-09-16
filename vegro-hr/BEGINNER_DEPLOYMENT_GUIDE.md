# 🚀 Beginner's Step-by-Step Deployment Guide

## 📋 What You'll Need
- Your DirectAdmin login credentials
- `backend-two-domain.tar.gz` file (from your project)
- `frontend-two-domain.tar.gz` file (from your project)
- Database name: `invodte1_vegro-hr`
- Database user: `invodte1_vegro-hr`
- Database password: `KbHCMxNFKJneGwjCVbLS`

---

## 🔐 STEP 1: Log in to DirectAdmin

1. Open your web browser
2. Go to your DirectAdmin login URL (e.g., `https://yourdomain.com:2222`)
3. Enter your username and password
4. Click **"Login"**

---

## 🖥️ STEP 2: Create Backend Subdomain

1. In DirectAdmin dashboard, look for **"Advanced Features"** section
2. Click on **"Subdomains"**
3. Click **"Create Subdomain"** button
4. **Subdomain name:** Enter `vegro-hr`
5. **Domain:** Should be your main domain (leave as is)
6. **Document Root:** Should automatically show `public_html/vegro-hr`
7. Click **"Create"**

✅ You should see a success message: "Subdomain created successfully"

---

## 🖥️ STEP 3: Create Frontend Subdomain

1. Stay in **"Subdomains"** section
2. Click **"Create Subdomain"** again
3. **Subdomain name:** Enter `app`
4. **Domain:** Should be your main domain (leave as is)
5. **Document Root:** Should automatically show `public_html/app`
6. Click **"Create"**

✅ You should see: "Subdomain created successfully"

---

## ⚙️ STEP 4: Configure PHP Version

1. In DirectAdmin dashboard, look for **"Extra Features"** section
2. Click on **"Select PHP Version"**
3. You'll see a list of PHP versions
4. Select **PHP 8.1** or **PHP 8.2** (anything 8.1 or higher)
5. Click **"Set as default"**
6. Wait for the page to load
7. Verify it shows the version you selected

✅ You should see the PHP version has changed

---

## 🗄️ STEP 5: Create MySQL Database

1. In DirectAdmin dashboard, look for **"Extra Features"** section
2. Click on **"MySQL Management"**
3. Under **"Create New Database"** section:
   - **Database Name:** Enter `invodte1_vegro-hr`
   - Click **"Create"**
4. Under **"Create New User"** section:
   - **Username:** Enter `invodte1_vegro-hr`
   - **Password:** Click **"Generate"** button
   - **Copy the password** and save it somewhere safe
   - Click **"Create"**
5. Under **"Add User to Database"** section:
   - **Database:** Select `invodte1_vegro-hr`
   - **User:** Select `invodte1_vegro-hr`
   - Click **"Add User to Database"**
6. On the next page, you'll see permissions checkboxes
7. Check **"ALL PRIVILEGES"** (this gives full access)
8. Click **"Save"**

✅ You should see: "User added to database successfully"

---

## 📁 STEP 6: Upload Backend Package

1. In DirectAdmin dashboard, look for **"File Manager"**
2. Click on **"File Manager"**
3. Navigate to `public_html` folder (click on it)
4. You should see a folder named `vegro-hr` (created in Step 2)
5. Click on the `vegro-hr` folder
6. Click **"Upload"** button (usually at the top)
7. Click **"Choose File"** or **"Browse"**
8. Select `backend-two-domain.tar.gz` from your computer
9. Click **"Upload"**
10. Wait for the upload to complete (you'll see a progress bar)

✅ You should see: "File uploaded successfully"

---

## 📦 STEP 7: Extract Backend Files

1. In File Manager, you should see `backend-two-domain.tar.gz`
2. Right-click on `backend-two-domain.tar.gz`
3. Select **"Extract"** from the menu
4. Click **"Extract File"** button
5. Wait for extraction to complete
6. You should see many folders and files appear (app, config, database, public, etc.)

✅ You should see Laravel project files extracted

---

## ⚙️ STEP 8: Configure Environment File

1. In File Manager, look for a file named `.env.backend`
2. Right-click on `.env.backend`
3. Select **"Rename"**
4. Change the name to `.env` (just remove `.backend`)
5. Click **"Rename"**
6. Right-click on the newly renamed `.env` file
7. Select **"Edit"**
8. Verify these settings:
   ```
   DB_DATABASE=invodte1_vegro-hr
   DB_USERNAME=invodte1_vegro-hr
   DB_PASSWORD=KbHCMxNFKJneGwjCVbLS
   DB_HOST=localhost
   APP_URL=https://vegro-hr.invodtechltd.com
   ```
9. If any of these are different, update them
10. Click **"Save"**

✅ Environment configuration updated

---

## 🔐 STEP 9: Set File Permissions

1. In File Manager, select the `storage` folder
2. Click **"Change Permissions"** (usually at the top)
3. Change the permission number to **777**
4. Click **"Save"**
5. Select the `bootstrap` folder
6. Click on it to enter the folder
7. Select the `cache` folder
8. Click **"Change Permissions"**
9. Change the permission number to **777**
10. Click **"Save"**
11. Click the ".." (go up) button twice to return to project root

✅ File permissions set

---

## 📁 STEP 10: Create Required Directories

1. In File Manager, click on the `storage` folder
2. Click **"Create Folder"**
3. Name it: `framework`
4. Click **"Create"**
5. Click on the new `framework` folder
6. Create 3 folders inside it:
   - `cache`
   - `sessions`
   - `views`
7. Go back to `storage` folder
8. Create a folder named `logs`

✅ Required directories created

---

## 🌐 STEP 11: Configure Document Root

1. Go back to **"Subdomains"** in DirectAdmin
2. Find your `vegro-hr` subdomain
3. Click **"Manage"** button next to it
4. Look for **"Document Root"** field
5. Change it to: `public_html/vegro-hr/public`
6. Click **"Save"**

✅ Document root configured

---

## 🔧 STEP 12: Run Setup Scripts (In Order)

**Important:** These scripts are web-based alternatives to SSH commands. The storage script copies files instead of creating a symlink because shared hosting often restricts the symlink() function.

### Script 1: Generate Application Key
1. Open a new browser tab
2. Visit: `https://vegro-hr.invodtechltd.com/generate_key.php`
3. You should see: "Application key generated successfully!"
4. Close the browser tab
5. Go back to DirectAdmin File Manager
6. Navigate to `public` folder (inside vegro-hr → public)
7. Find and delete `generate_key.php`

### Script 2: Copy Storage Files
1. Open a new browser tab
2. Visit: `https://vegro-hr.invodtechltd.com/copy_storage.php`
3. **If successful:** You'll see "Storage copy completed successfully!"
   - Close tab, go to File Manager, delete `copy_storage.php`
4. **If it fails:**
   - This should not fail as it copies files instead of creating a symlink
   - If it does fail, check file permissions on the storage directories

### Script 3: Run Database Migrations
1. Open a new browser tab
2. Visit: `https://vegro-hr.invodtechltd.com/migrate.php`
3. You should see: "Migration completed successfully!" (or list of tables)
4. Close the browser tab
5. Go to File Manager → `public` folder
6. Delete `migrate.php`

### Script 4: Clear Cache
1. Open a new browser tab
2. Visit: `https://vegro-hr.invodtechltd.com/clear_cache.php`
3. You should see: "Cache cleared successfully!"
4. Close the browser tab
5. Go to File Manager → `public` folder
6. Delete `clear_cache.php`

✅ All setup scripts completed

---

## 🔒 STEP 13: Enable SSL for Backend

1. Go to **"SSL Certificates"** in DirectAdmin
2. Find your `vegro-hr` subdomain
3. Click **"Free & Auto"** (Let's Encrypt) button
4. Click **"Save"**
5. Wait for SSL to be issued (usually takes 1-2 minutes)

✅ SSL enabled for backend

---

## ✅ STEP 14: Test Backend API

1. Open a new browser tab
2. Visit: `https://vegro-hr.invodtechltd.com/`
3. You should see JSON response like this:
```json
{
  "message": "Vegro HR API",
  "version": "1.0.0",
  "frontend": "https://app.vegro-hr.invodtechltd.com",
  "api_endpoints": {...}
}
```

✅ Backend is working!

---

## 📁 STEP 15: Upload Frontend Package

1. In DirectAdmin File Manager, go to `public_html` folder
2. Click on the `app` folder (created in Step 3)
3. Click **"Upload"**
4. Select `frontend-two-domain.tar.gz` from your computer
5. Click **"Upload"**
6. Wait for upload to complete

✅ Frontend package uploaded

---

## 📦 STEP 16: Extract Frontend Files

1. Right-click on `frontend-two-domain.tar.gz`
2. Select **"Extract"**
3. Click **"Extract File"**
4. Wait for extraction
5. You should see `index.html` and `assets` folder

✅ Frontend files extracted

---

## ⚙️ STEP 17: Configure Frontend

1. Look for a file named `frontend-htaccess`
2. Right-click on it
3. Select **"Rename"**
4. Change name to `.htaccess` (dot at the beginning)
5. Click **"Rename"**

✅ Frontend configured

---

## 🌐 STEP 18: Configure Frontend Document Root

1. Go to **"Subdomains"** in DirectAdmin
2. Find your `app` subdomain
3. Click **"Manage"**
4. Change **Document Root** to: `public_html/app`
5. Click **"Save"**

✅ Frontend document root configured

---

## 🔒 STEP 19: Enable SSL for Frontend

1. Go to **"SSL Certificates"** in DirectAdmin
2. Find your `app` subdomain
3. Click **"Free & Auto"** (Let's Encrypt)
4. Click **"Save"**
5. Wait for SSL to be issued

✅ SSL enabled for frontend

---

## ✅ STEP 20: Test Frontend

1. Open a new browser tab
2. Visit: `https://app.vegro-hr.invodtechltd.com`
3. You should see the Vegro HR login page with a dark theme
4. The page should load without errors

✅ Frontend is working!

---

## 🎉 CONGRATULATIONS!

Your HR Management Platform is now deployed and running!

**Your URLs:**
- **Backend API:** `https://vegro-hr.invodtechltd.com`
- **Frontend App:** `https://app.vegro-hr.invodtechltd.com`

**Next Steps:**
1. Create a super admin account using the API
2. Onboard your first company
3. Test the login link features
4. Start using your HR Management Platform!

---

## 🆘 If Something Goes Wrong

### 500 Internal Server Error on Backend:
- Check if PHP version is 8.1+
- Verify file permissions on `storage` and `bootstrap/cache` are 777
- Check if `.env` file exists in the root

### Database Connection Error:
- Verify database exists in MySQL Management
- Check database credentials in `.env` file
- Ensure database user has all privileges

### Frontend Not Loading:
- Check if files were extracted correctly
- Verify document root points to `public_html/app`
- Check if `.htaccess` file exists and is named correctly
- Try clearing browser cache

### Helper Scripts Failed:
- Check if script files exist in `public/` folder
- Verify PHP can execute scripts
- Check error messages for specific issues

Good luck with your deployment! 🚀
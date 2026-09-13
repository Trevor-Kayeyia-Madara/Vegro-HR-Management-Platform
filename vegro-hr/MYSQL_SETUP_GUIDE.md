# Vegro HR MySQL Setup Guide

## Development Environment (XAMPP)

### Prerequisites
- XAMPP installed with MySQL
- PHP 8.2 or higher
- Composer installed

### Setup Steps

1. **Configure Database Connection**
   - The `.env` file is already configured for XAMPP MySQL
   - Database: `vegro_hr`
   - Username: `root`
   - Password: (empty - default XAMPP)
   - Host: `localhost`
   - Port: `3306`

2. **Create Database**
   ```bash
   # Using XAMPP MySQL
   C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS vegro_hr;"
   ```

3. **Run Migrations**
   ```bash
   php artisan migrate
   ```

4. **Start Development Server**
   ```bash
   php artisan serve
   ```

5. **Access Application**
   - URL: `http://localhost:8000`

## Production Environment (DirectAdmin)

### Prerequisites
- DirectAdmin hosting account
- MySQL database access
- PHP 8.2 or higher
- Composer access on server

### Setup Steps

1. **Create Production Database**
   - Log in to DirectAdmin
   - Navigate to MySQL Management
   - Create new database: `vegro_hr_production`
   - Create database user with strong password
   - Grant all privileges to the user

2. **Upload Files to Server**
   - Upload all project files to your public_html directory
   - Exclude `.env`, `vendor/`, `node_modules/`

3. **Configure Production Environment**
   ```bash
   # Copy production environment file
   cp .env.production .env
   
   # Update the following values in .env:
   # DB_DATABASE=vegro_hr_production
   # DB_USERNAME=your_directadmin_db_user
   # DB_PASSWORD=your_strong_password
   # APP_URL=https://your-domain.com
   # APP_DEBUG=false
   ```

4. **Install Dependencies on Server**
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

5. **Generate Application Key**
   ```bash
   php artisan key:generate
   ```

6. **Run Migrations**
   ```bash
   php artisan migrate --force
   ```

7. **Set Permissions**
   ```bash
   chmod -R 755 storage
   chmod -R 755 bootstrap/cache
   ```

8. **Optimize Application**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

## Security Guard Payroll Calculation

The system includes a specialized payroll calculation service for security guards with overtime:

### Calculation Formula
- **Gross Salary**: Basic salary (e.g., KES 18,000)
- **Daily Rate**: Gross Salary ÷ 30.33 days
- **Hourly Rate**: Daily Rate ÷ 12 hours
- **Overtime Rate**: Hourly Rate × 1.5
- **Overtime Allowance**: Overtime Hours × Overtime Rate
- **Total Gross**: Basic Salary + Overtime Allowance

### Statutory Deductions (Kenya)
- **NSSF**: 6% of pensionable earnings (Tier 1: up to KES 9,000, Tier 2: KES 9,001-108,000)
- **SHIF**: 2.75% of gross salary (minimum KES 300)
- **Housing Levy**: 1.5% of gross salary
- **PAYE**: Progressive tax based on Kenyan tax bands
- **Personal Relief**: KES 2,400

### Example Calculation
For a security guard with KES 18,000 basic salary and 72 hours overtime:

- Daily Rate: KES 593.47
- Hourly Rate: KES 49.46
- Overtime Rate: KES 74.18
- Overtime Allowance: KES 5,341.25
- Total Gross: KES 23,341.25
- Net Salary: KES 21,496.25 (after deductions)

### Using the Payroll Service

```php
use App\Services\PayrollCalculationService;
use App\Models\Employee;

$employee = Employee::find($employeeId);
$service = new PayrollCalculationService();

// Calculate payroll for September 2026 with 72 hours overtime
$calculation = $service->calculateSecurityGuardPayroll($employee, 9, 2026, 72);

// Create payroll record
$payroll = $service->createPayroll($employee, 9, 2026, 72);
```

## Database Structure

### Key Tables for Payroll
- `employees` - Employee information with overtime configuration
- `payrolls` - Monthly payroll records with detailed calculations
- `tax_profiles` - Kenyan tax configuration (PAYE bands, NSSF, SHIF rates)
- `attendances` - Daily attendance and overtime tracking
- `payslips` - Generated payslip records

### Employee Overtime Configuration
Employees can be configured with:
- `has_overtime` - Boolean flag for overtime eligibility
- `daily_overtime_hours` - Standard daily overtime hours
- `hourly_rate` - Custom hourly rate (overrides calculated rate)
- `overtime_rate_multiplier` - Custom overtime multiplier (default 1.5)
- `monthly_overtime_hours` - Expected monthly overtime hours

## Troubleshooting

### Database Connection Issues
- Verify MySQL service is running in XAMPP
- Check database credentials in `.env` file
- Ensure database exists and user has permissions

### Migration Errors
- Drop and recreate database: `DROP DATABASE vegro_hr; CREATE DATABASE vegro_hr;`
- Run migrations again: `php artisan migrate:fresh`

### Permission Issues (Production)
- Ensure storage directory is writable: `chmod -R 755 storage`
- Check file ownership matches web server user

## Maintenance

### Regular Tasks
- Backup database regularly
- Monitor disk space for logs
- Update dependencies: `composer update`
- Clear caches: `php artisan cache:clear`

### Database Backups
```bash
# Backup
mysqldump -u username -p database_name > backup.sql

# Restore
mysql -u username -p database_name < backup.sql
```

## Support

For issues or questions related to:
- Database setup: Check MySQL logs
- Application errors: Check Laravel logs in `storage/logs`
- Payroll calculations: Review tax profile configuration in database
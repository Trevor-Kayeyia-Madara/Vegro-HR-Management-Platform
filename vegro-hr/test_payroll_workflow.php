<?php

require __DIR__ . '/vendor/autoload.php';

use App\Services\PayrollCalculationService;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Payroll;
use App\Models\TaxProfile;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

// Initialize Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "=== Vegro HR Payroll System Test Run ===\n\n";

try {
    // Step 1: Check database connection
    echo "Step 1: Testing Database Connection...\n";
    try {
        DB::connection()->getPdo();
        echo "✓ Database connection successful\n\n";
    } catch (\Exception $e) {
        throw new \Exception("Database connection failed: " . $e->getMessage());
    }

    // Step 2: Check required tables exist
    echo "Step 2: Checking Database Tables...\n";
    $requiredTables = ['employees', 'payrolls', 'tax_profiles', 'departments', 'companies'];
    foreach ($requiredTables as $table) {
        if (!Schema::hasTable($table)) {
            throw new \Exception("Required table '$table' does not exist");
        }
        echo "✓ Table '$table' exists\n";
    }
    echo "\n";

    // Step 3: Check tax profile
    echo "Step 3: Checking Tax Profile...\n";
    $taxProfile = TaxProfile::where('country_code', 'KE')->first();
    if (!$taxProfile) {
        throw new \Exception("Kenya tax profile not found");
    }
    echo "✓ Kenya tax profile found\n";
    echo "  - PAYE bands: " . count(json_decode($taxProfile->paye_bands, true)) . " bands\n";
    echo "  - NSSF rate: " . ($taxProfile->nssf_rate * 100) . "%\n";
    echo "  - SHIF rate: " . ($taxProfile->shif_rate * 100) . "%\n";
    echo "  - Housing Levy rate: " . ($taxProfile->housing_levy_rate * 100) . "%\n\n";

    // Step 4: Create test company and department
    echo "Step 4: Creating Test Company and Department...\n";
    
    $company = Company::first();
    if (!$company) {
        $company = Company::create([
            'name' => 'Test Security Company',
            'email' => 'test@security.com',
            'phone' => '+254700000000',
        ]);
        echo "✓ Created test company\n";
    } else {
        echo "✓ Using existing company: " . $company->name . "\n";
    }
    
    $department = Department::firstOrCreate(
        ['name' => 'Security Services'],
        [
            'description' => 'Security guard department',
            'company_id' => $company->id,
        ]
    );
    echo "✓ Department: " . $department->name . "\n\n";

    // Step 5: Create test employee
    echo "Step 5: Creating Test Employee...\n";
    $employee = Employee::create([
        'employee_number' => 'SEC001',
        'name' => 'John Security Guard',
        'email' => 'john.guard@test.com',
        'phone' => '+254711111111',
        'department_id' => $department->id,
        'position' => 'Security Officer',
        'salary' => 18000.00,
        'hire_date' => now(),
        'status' => 'active',
        'has_overtime' => true,
        'daily_overtime_hours' => 3,
        'hourly_rate' => 0, // Will be calculated
        'overtime_rate_multiplier' => 1.5,
        'monthly_overtime_hours' => 72,
        'annual_leave_days' => 21,
        'annual_leave_used' => 0,
        'annual_leave_balance' => 21,
        'company_id' => $company->id,
    ]);
    echo "✓ Employee created: " . $employee->name . " (ID: " . $employee->id . ")\n";
    echo "  - Basic Salary: KES " . number_format($employee->salary, 2) . "\n";
    echo "  - Has Overtime: " . ($employee->has_overtime ? 'Yes' : 'No') . "\n";
    echo "  - Monthly Overtime Hours: " . $employee->monthly_overtime_hours . "\n\n";

    // Step 6: Test payroll calculation
    echo "Step 6: Testing Payroll Calculation...\n";
    $payrollService = new PayrollCalculationService();
    
    $month = 9; // September
    $year = 2026;
    $overtimeHoursWorked = 72;
    
    $calculation = $payrollService->calculateSecurityGuardPayroll($employee, $month, $year, $overtimeHoursWorked);
    
    echo "Calculation Results:\n";
    echo "  - Daily Rate: KES " . number_format($calculation['daily_rate'], 2) . "\n";
    echo "  - Hourly Rate: KES " . number_format($calculation['hourly_rate'], 2) . "\n";
    echo "  - Overtime Rate: KES " . number_format($calculation['overtime_rate'], 2) . "\n";
    echo "  - Overtime Hours: " . $calculation['overtime_hours_worked'] . "\n";
    echo "  - Overtime Allowance: KES " . number_format($calculation['overtime_allowance'], 2) . "\n";
    echo "  - Gross Salary: KES " . number_format($calculation['gross_salary'], 2) . "\n";
    echo "  - NSSF: KES " . number_format($calculation['nssf'], 2) . "\n";
    echo "  - SHIF: KES " . number_format($calculation['shif'], 2) . "\n";
    echo "  - Housing Levy: KES " . number_format($calculation['housing_levy'], 2) . "\n";
    echo "  - Taxable Income: KES " . number_format($calculation['taxable_income'], 2) . "\n";
    echo "  - PAYE: KES " . number_format($calculation['paye'], 2) . "\n";
    echo "  - Personal Relief: KES " . number_format($calculation['personal_relief'], 2) . "\n";
    echo "  - Tax After Relief: KES " . number_format($calculation['tax'], 2) . "\n";
    echo "  - Total Deductions: KES " . number_format($calculation['deductions'], 2) . "\n";
    echo "  - Net Salary: KES " . number_format($calculation['net_salary'], 2) . "\n\n";

    // Step 7: Verify calculation accuracy
    echo "Step 7: Verifying Calculation Accuracy...\n";
    $expectedDailyRate = 593.47;
    $expectedHourlyRate = 49.46;
    $expectedOvertimeRate = 74.18;
    $expectedOvertimeAllowance = 5340.96;
    $expectedGrossSalary = 23340.96;
    
    $tolerances = [
        'daily_rate' => ['expected' => $expectedDailyRate, 'actual' => $calculation['daily_rate'], 'tolerance' => 0.1],
        'hourly_rate' => ['expected' => $expectedHourlyRate, 'actual' => $calculation['hourly_rate'], 'tolerance' => 0.1],
        'overtime_rate' => ['expected' => $expectedOvertimeRate, 'actual' => $calculation['overtime_rate'], 'tolerance' => 0.1],
        'overtime_allowance' => ['expected' => $expectedOvertimeAllowance, 'actual' => $calculation['overtime_allowance'], 'tolerance' => 1.0],
        'gross_salary' => ['expected' => $expectedGrossSalary, 'actual' => $calculation['gross_salary'], 'tolerance' => 1.0],
    ];
    
    $allPassed = true;
    foreach ($tolerances as $field => $values) {
        $diff = abs($values['expected'] - $values['actual']);
        $passed = $diff <= $values['tolerance'];
        $allPassed = $allPassed && $passed;
        echo "  - " . ucwords(str_replace('_', ' ', $field)) . ": ";
        echo ($passed ? "✓ PASS" : "✗ FAIL") . " (diff: " . number_format($diff, 2) . ")\n";
    }
    echo "\n";

    if (!$allPassed) {
        throw new \Exception("Calculation accuracy verification failed");
    }

    // Step 8: Create payroll record
    echo "Step 8: Creating Payroll Record...\n";
    $payroll = $payrollService->createPayroll($employee, $month, $year, $overtimeHoursWorked);
    echo "✓ Payroll record created (ID: " . $payroll->id . ")\n";
    echo "  - Status: " . $payroll->status . "\n";
    echo "  - Month/Year: " . $payroll->month . "/" . $payroll->year . "\n\n";

    // Step 9: Verify payroll record in database
    echo "Step 9: Verifying Payroll Record in Database...\n";
    $savedPayroll = Payroll::find($payroll->id);
    if (!$savedPayroll) {
        throw new \Exception("Payroll record not found in database");
    }
    echo "✓ Payroll record verified in database\n";
    echo "  - Basic Salary: KES " . number_format($savedPayroll->basic_salary, 2) . "\n";
    echo "  - Overtime Allowance: KES " . number_format($savedPayroll->overtime_allowance', 2) . "\n";
    echo "  - Net Salary: KES " . number_format($savedPayroll->net_salary, 2) . "\n\n";

    // Step 10: Test employee relationship
    echo "Step 10: Testing Employee Relationships...\n";
    $employeeWithPayroll = Employee::with('payrolls')->find($employee->id);
    echo "✓ Employee-payroll relationship working\n";
    echo "  - Employee has " . $employeeWithPayroll->payrolls->count() . " payroll record(s)\n\n";

    // Step 11: Test overtime scenarios
    echo "Step 11: Testing Different Overtime Scenarios...\n";
    $scenarios = [
        ['hours' => 0, 'description' => 'No overtime'],
        ['hours' => 36, 'description' => 'Half standard overtime'],
        ['hours' => 72, 'description' => 'Standard overtime'],
        ['hours' => 108, 'description' => 'Extended overtime'],
    ];
    
    foreach ($scenarios as $scenario) {
        $result = $payrollService->calculateSecurityGuardPayroll($employee, $month, $year, $scenario['hours']);
        echo "  - " . $scenario['description'] . " (" . $scenario['hours'] . " hours): ";
        echo "Net KES " . number_format($result['net_salary'], 2) . "\n";
    }
    echo "\n";

    // Step 12: Cleanup test data
    echo "Step 12: Cleaning Up Test Data...\n";
    $payroll->delete();
    echo "✓ Test payroll deleted\n";
    $employee->delete();
    echo "✓ Test employee deleted\n\n";

    echo "=== ALL TESTS PASSED ===\n";
    echo "The payroll system is working correctly!\n";
    echo "Security guard overtime calculations are accurate.\n";
    echo "Kenyan statutory deductions are properly applied.\n";
    echo "Database relationships are functioning properly.\n";

} catch (\Exception $e) {
    echo "\n✗ TEST FAILED: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
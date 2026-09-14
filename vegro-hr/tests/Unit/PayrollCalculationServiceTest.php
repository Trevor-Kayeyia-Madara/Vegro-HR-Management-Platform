<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\TaxProfile;
use App\Services\PayrollCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        TaxProfile::create([
            'name' => 'Kenya PAYE (Monthly)',
            'country_code' => 'KE',
            'currency' => 'KES',
            'paye_bands' => [
                ['limit' => 24000, 'rate' => 0.10],
                ['limit' => 8333, 'rate' => 0.25],
                ['limit' => 467667, 'rate' => 0.30],
                ['limit' => 300000, 'rate' => 0.325],
                ['limit' => null, 'rate' => 0.35],
            ],
            'personal_relief' => 2400,
            'insurance_relief_rate' => 0.15,
            'insurance_relief_cap' => 5000,
            'pension_cap' => 30000,
            'mortgage_cap' => 30000,
            'nssf_rate' => 0.06,
            'nssf_tier1_limit' => 9000,
            'nssf_tier2_limit' => 108000,
            'nssf_max' => 6480,
            'shif_rate' => 0.0275,
            'shif_min' => 300,
            'housing_levy_rate' => 0.015,
        ]);
    }

    public function test_it_applies_latest_kenyan_overtime_and_tax_rates(): void
    {
        $employee = Employee::create([
            'employee_number' => 'SEC-001',
            'name' => 'John Security Guard',
            'email' => 'john.guard@example.com',
            'phone' => '+254700000000',
            'department_id' => 1,
            'position' => 'Security Officer',
            'salary' => 18000.00,
            'hire_date' => '2026-09-01',
            'status' => 'active',
            'has_overtime' => true,
            'daily_overtime_hours' => 3,
            'hourly_rate' => 0,
            'overtime_rate_multiplier' => 1.5,
            'monthly_overtime_hours' => 72,
            'annual_leave_days' => 21,
            'annual_leave_used' => 0,
            'annual_leave_balance' => 21,
            'company_id' => 1,
        ]);

        $result = app(PayrollCalculationService::class)->calculateSecurityGuardPayroll($employee, 9, 2026, 72);

        $this->assertEqualsWithDelta(593.47, $result['daily_rate'], 0.1);
        $this->assertEqualsWithDelta(49.46, $result['hourly_rate'], 0.1);
        $this->assertEqualsWithDelta(74.18, $result['overtime_rate'], 0.1);
        $this->assertEqualsWithDelta(5341.25, $result['overtime_allowance'], 1.0);
        $this->assertEqualsWithDelta(23341.25, $result['gross_salary'], 1.0);
        $this->assertEqualsWithDelta(1080.00, $result['nssf'], 0.01);
        $this->assertEqualsWithDelta(495.00, $result['shif'], 0.01);
        $this->assertEqualsWithDelta(270.00, $result['housing_levy'], 0.01);
        $this->assertEqualsWithDelta(21496.25, $result['taxable_income'], 0.01);
        $this->assertEqualsWithDelta(2149.62, $result['paye'], 0.01);
        $this->assertEqualsWithDelta(0.00, $result['tax'], 0.01);
        $this->assertEqualsWithDelta(21496.25, $result['net_salary'], 0.01);
    }
}

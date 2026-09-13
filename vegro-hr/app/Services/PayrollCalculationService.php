<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\TaxProfile;
use Illuminate\Support\Facades\DB;

class PayrollCalculationService
{
    /**
     * Calculate payroll for a security guard with overtime
     * 
     * @param Employee $employee
     * @param int $month
     * @param int $year
     * @param float $overtimeHoursWorked
     * @return array
     */
    public function calculateSecurityGuardPayroll(Employee $employee, int $month, int $year, float $overtimeHoursWorked = 0): array
    {
        // Constants for calculation
        $daysPerMonth = 30.33;
        $hoursPerDay = 12;
        $overtimeMultiplier = 1.5;
        
        // Get employee salary (gross)
        $grossSalary = $employee->salary;
        
        // Daily rate calculation
        $dailyRate = $grossSalary / $daysPerMonth;
        
        // Hourly rate calculation
        $hourlyRate = $dailyRate / $hoursPerDay;
        
        // Overtime rate calculation
        $overtimeRate = $hourlyRate * $overtimeMultiplier;
        
        // Overtime allowance calculation
        $overtimeAllowance = $overtimeHoursWorked * $overtimeRate;
        
        // Total gross salary including overtime
        $totalGrossSalary = $grossSalary + $overtimeAllowance;
        
        // Calculate statutory deductions (Kenya)
        $taxProfile = TaxProfile::where('country_code', 'KE')->first();
        
        $nssf = $this->calculateNSSF($grossSalary, $taxProfile);
        $shif = $this->calculateSHIF($grossSalary, $taxProfile);
        $housingLevy = $this->calculateHousingLevy($grossSalary, $taxProfile);
        
        // Taxable income
        $taxableIncome = $totalGrossSalary - $nssf - $shif - $housingLevy;
        
        // PAYE calculation
        $paye = $this->calculatePAYE($taxableIncome, $taxProfile);
        
        // Personal relief
        $personalRelief = $taxProfile->personal_relief ?? 2400;
        
        // Net tax after relief
        $netTax = max(0, $paye - $personalRelief);
        
        // Total deductions
        $totalDeductions = $nssf + $shif + $housingLevy + $netTax;
        
        // Net salary
        $netSalary = $totalGrossSalary - $totalDeductions;
        
        return [
            'basic_salary' => $grossSalary,
            'daily_rate' => $dailyRate,
            'hourly_rate' => $hourlyRate,
            'overtime_rate' => $overtimeRate,
            'overtime_hours_worked' => $overtimeHoursWorked,
            'overtime_allowance' => $overtimeAllowance,
            'gross_salary' => $totalGrossSalary,
            'nssf' => $nssf,
            'shif' => $shif,
            'housing_levy' => $housingLevy,
            'taxable_income' => $taxableIncome,
            'paye' => $paye,
            'personal_relief' => $personalRelief,
            'tax' => $netTax,
            'deductions' => $totalDeductions,
            'net_salary' => $netSalary,
        ];
    }
    
    /**
     * Calculate NSSF contribution
     */
    private function calculateNSSF(float $salary, ?TaxProfile $taxProfile): float
    {
        if (!$taxProfile) {
            return 0;
        }
        
        $tier1Limit = $taxProfile->nssf_tier1_limit ?? 9000;
        $tier2Limit = $taxProfile->nssf_tier2_limit ?? 108000;
        $nssfRate = $taxProfile->nssf_rate ?? 0.06;
        $nssfMax = $taxProfile->nssf_max ?? 6480;
        
        // Tier 1: 6% of pensionable earnings up to KES 9,000
        $tier1Contribution = min($salary, $tier1Limit) * $nssfRate;
        
        // Tier 2: 6% of pensionable earnings between KES 9,001 and KES 108,000
        $tier2Pensionable = max(0, min($salary - $tier1Limit, $tier2Limit - $tier1Limit));
        $tier2Contribution = $tier2Pensionable * $nssfRate;
        
        $totalNssf = $tier1Contribution + $tier2Contribution;
        
        return min($totalNssf, $nssfMax);
    }
    
    /**
     * Calculate SHIF contribution
     */
    private function calculateSHIF(float $salary, ?TaxProfile $taxProfile): float
    {
        if (!$taxProfile) {
            return 0;
        }
        
        $shifRate = $taxProfile->shif_rate ?? 0.0275;
        $shifMin = $taxProfile->shif_min ?? 300;
        
        $shif = $salary * $shifRate;
        
        return max($shif, $shifMin);
    }
    
    /**
     * Calculate Housing Levy
     */
    private function calculateHousingLevy(float $salary, ?TaxProfile $taxProfile): float
    {
        if (!$taxProfile) {
            return 0;
        }
        
        $housingLevyRate = $taxProfile->housing_levy_rate ?? 0.015;
        
        return $salary * $housingLevyRate;
    }
    
    /**
     * Calculate PAYE based on Kenyan tax bands
     */
    private function calculatePAYE(float $taxableIncome, ?TaxProfile $taxProfile): float
    {
        if (!$taxProfile) {
            return 0;
        }
        
        $payeBands = is_string($taxProfile->paye_bands) ? json_decode($taxProfile->paye_bands, true) : $taxProfile->paye_bands;
        $personalRelief = $taxProfile->personal_relief ?? 2400;
        
        $totalPAYE = 0;
        $remainingIncome = $taxableIncome;
        
        foreach ($payeBands as $band) {
            if ($remainingIncome <= 0) {
                break;
            }
            
            $limit = $band['limit'] ?? PHP_FLOAT_MAX;
            $rate = $band['rate'] ?? 0;
            
            $taxableAtThisRate = min($remainingIncome, $limit);
            $totalPAYE += $taxableAtThisRate * $rate;
            
            $remainingIncome -= $taxableAtThisRate;
        }
        
        return $totalPAYE;
    }
    
    /**
     * Create payroll record for employee
     */
    public function createPayroll(Employee $employee, int $month, int $year, float $overtimeHoursWorked = 0): Payroll
    {
        $calculation = $this->calculateSecurityGuardPayroll($employee, $month, $year, $overtimeHoursWorked);
        
        $taxProfile = TaxProfile::where('country_code', 'KE')->first();
        
        $payroll = Payroll::create([
            'employee_id' => $employee->id,
            'tax_profile_id' => $taxProfile ? $taxProfile->id : null,
            'month' => $month,
            'year' => $year,
            'basic_salary' => $calculation['basic_salary'],
            'allowances' => 0,
            'gross_salary' => $calculation['gross_salary'],
            'daily_rate' => $calculation['daily_rate'],
            'hourly_rate' => $calculation['hourly_rate'],
            'overtime_rate' => $calculation['overtime_rate'],
            'overtime_hours_worked' => $calculation['overtime_hours_worked'],
            'overtime_allowance' => $calculation['overtime_allowance'],
            'nssf' => $calculation['nssf'],
            'shif' => $calculation['shif'],
            'housing_levy' => $calculation['housing_levy'],
            'taxable_income' => $calculation['taxable_income'],
            'paye' => $calculation['paye'],
            'tax_rate' => $calculation['paye'] / max(1, $calculation['taxable_income']),
            'personal_relief' => $calculation['personal_relief'],
            'insurance_premium' => 0,
            'insurance_relief' => 0,
            'pension_contribution' => 0,
            'mortgage_interest' => 0,
            'deductions' => $calculation['deductions'],
            'tax' => $calculation['tax'],
            'net_salary' => $calculation['net_salary'],
            'status' => 'pending',
            'company_id' => $employee->company_id,
        ]);
        
        return $payroll;
    }
}
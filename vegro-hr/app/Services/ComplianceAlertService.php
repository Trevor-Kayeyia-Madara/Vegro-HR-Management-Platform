<?php

namespace App\Services;

use App\Models\Company;
use App\Models\ComplianceAlert;
use App\Models\Payroll;
use App\Models\Payslip;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;

class ComplianceAlertService
{
    public function scanAllCompanies(): array
    {
        $results = [];
        Company::query()->select(['id'])->chunkById(100, function ($companies) use (&$results) {
            foreach ($companies as $company) {
                $results[] = $this->scanCompany((int) $company->id);
            }
        });
        return $results;
    }

    public function scanCompany(int $companyId): array
    {
        $created = 0;
        $created += $this->detectUnapprovedClosedPayrollPeriods($companyId);
        $created += $this->detectApprovedNotIssuedPayslips($companyId);
        $created += $this->detectNegativeLeaveBalances($companyId);

        return [
            'company_id' => $companyId,
            'alerts_created' => $created,
        ];
    }

    protected function detectUnapprovedClosedPayrollPeriods(int $companyId): int
    {
        $previousMonth = Carbon::now()->subMonth();
        $month = (int) $previousMonth->month;
        $year = (int) $previousMonth->year;

        $count = Payroll::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('month', $month)
            ->where('year', $year)
            ->where('status', '!=', 'approved')
            ->count();

        if ($count <= 0) {
            return 0;
        }

        return $this->upsertAlertAndNotify(
            $companyId,
            "payroll_unapproved_{$year}_{$month}",
            'high',
            'Unapproved payrolls from closed period',
            "Detected {$count} payroll records for {$month}/{$year} that are still not approved.",
            ['month' => $month, 'year' => $year, 'count' => $count]
        );
    }

    protected function detectApprovedNotIssuedPayslips(int $companyId): int
    {
        $threshold = Carbon::now()->subDays(7);
        $count = Payslip::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('status', 'approved')
            ->whereNotNull('approved_at')
            ->where('approved_at', '<=', $threshold)
            ->count();

        if ($count <= 0) {
            return 0;
        }

        return $this->upsertAlertAndNotify(
            $companyId,
            'payslips_approved_not_issued',
            'medium',
            'Approved payslips pending issue',
            "Detected {$count} approved payslips older than 7 days that are not issued.",
            ['count' => $count, 'threshold_date' => $threshold->toDateString()]
        );
    }

    protected function detectNegativeLeaveBalances(int $companyId): int
    {
        $count = \App\Models\EmployeeLeaveBalance::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('balance_days', '<', 0)
            ->count();

        if ($count <= 0) {
            return 0;
        }

        return $this->upsertAlertAndNotify(
            $companyId,
            'negative_leave_balances',
            'low',
            'Negative leave balances detected',
            "Detected {$count} employee leave balances below zero. Review policy and approvals.",
            ['count' => $count]
        );
    }

    protected function upsertAlertAndNotify(
        int $companyId,
        string $code,
        string $severity,
        string $title,
        string $message,
        array $data = []
    ): int {
        $existing = ComplianceAlert::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('code', $code)
            ->whereNull('acknowledged_at')
            ->first();

        if ($existing) {
            $existing->update([
                'severity' => $severity,
                'title' => $title,
                'message' => $message,
                'data' => $data,
                'detected_at' => now(),
            ]);
            return 0;
        }

        $alert = ComplianceAlert::create([
            'company_id' => $companyId,
            'code' => $code,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'detected_at' => now(),
        ]);

        $this->notifyStakeholders($companyId, $alert);
        return 1;
    }

    protected function notifyStakeholders(int $companyId, ComplianceAlert $alert): void
    {
        $roleIds = Role::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereIn('title', ['companyadmin', 'HR', 'Finance', 'Finance Manager', 'Director', 'MD'])
            ->pluck('id');

        if ($roleIds->isEmpty()) {
            return;
        }

        $users = User::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereIn('role_id', $roleIds)
            ->get(['id']);

        foreach ($users as $user) {
            \App\Models\InAppNotification::create([
                'company_id' => $companyId,
                'user_id' => $user->id,
                'type' => 'compliance.alert',
                'title' => "[{$alert->severity}] {$alert->title}",
                'message' => $alert->message,
                'data' => [
                    'alert_id' => $alert->id,
                    'code' => $alert->code,
                    'severity' => $alert->severity,
                ],
            ]);
        }
    }
}

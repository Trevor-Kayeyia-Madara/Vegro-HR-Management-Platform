<?php

namespace App\Console\Commands;

use App\Services\ComplianceAlertService;
use Illuminate\Console\Command;

class RunComplianceCheck extends Command
{
    protected $signature = 'vegro:compliance-check {--company_id= : Run for a specific company id}';
    protected $description = 'Run automated compliance checks and generate alerts';

    public function handle(ComplianceAlertService $complianceAlertService): int
    {
        $companyId = $this->option('company_id');

        if ($companyId) {
            $result = $complianceAlertService->scanCompany((int) $companyId);
            $this->info("Company {$result['company_id']} alerts created: {$result['alerts_created']}");
            return self::SUCCESS;
        }

        $results = $complianceAlertService->scanAllCompanies();
        $total = collect($results)->sum('alerts_created');
        $this->info('Compliance scan complete. Alerts created: ' . $total);

        return self::SUCCESS;
    }
}

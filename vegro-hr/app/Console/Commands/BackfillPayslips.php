<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payroll;
use App\Services\PayslipService;

class BackfillPayslips extends Command
{
    protected $signature = 'vegro:backfill-payslips {--force : Rebuild all payslips even if issued/approved}';
    protected $description = 'Backfill payslips from payroll data';

    protected PayslipService $payslipService;

    public function __construct(PayslipService $payslipService)
    {
        parent::__construct();
        $this->payslipService = $payslipService;
    }

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $query = Payroll::with(['employee', 'payslip']);

        $total = $query->count();
        if ($total === 0) {
            $this->info('No payroll records found.');
            return self::SUCCESS;
        }

        $this->info("Processing {$total} payroll records...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunkById(100, function ($payrolls) use ($force, $bar) {
            foreach ($payrolls as $payroll) {
                if ($payroll->payslip) {
                    if ($force) {
                        $this->payslipService->syncPayslipForPayroll($payroll->load('employee', 'payslip'));
                    }
                    $bar->advance();
                    continue;
                }

                $this->payslipService->createPayslip(['payroll_id' => $payroll->id]);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info('Payslip backfill complete.');

        return self::SUCCESS;
    }
}

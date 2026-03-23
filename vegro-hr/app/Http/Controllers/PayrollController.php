<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\TaxProfile;
use App\Helpers\ApiResponse;
use App\Services\PayslipService;
use Illuminate\Http\Request;
use App\Http\Resources\PayrollResource;
use OpenApi\Attributes as OA;

#[OA\Info(title: "Payroll API", version: "1.0.0")]

class PayrollController extends Controller
{
    protected PayslipService $payslipService;

    public function __construct(PayslipService $payslipService)
    {
        $this->payslipService = $payslipService;
    }

    protected function calculateNssf(float $gross, ?TaxProfile $profile): float
    {
        $rate = (float) ($profile?->nssf_rate ?? 0.06);
        $tierOneLimit = (float) ($profile?->nssf_tier1_limit ?? 9000);
        $tierTwoLimit = (float) ($profile?->nssf_tier2_limit ?? 108000);
        $max = (float) ($profile?->nssf_max ?? 6480);

        $tierOne = min($gross, $tierOneLimit) * $rate;
        $tierTwo = 0;

        if ($gross > $tierOneLimit) {
            $tierTwoBase = min($gross, $tierTwoLimit) - $tierOneLimit;
            $tierTwo = $tierTwoBase * $rate;
        }

        return round(min($tierOne + $tierTwo, $max), 2);
    }

    protected function calculatePaye(float $taxableIncome, ?TaxProfile $profile): float
    {
        $bands = $profile?->paye_bands ?? [
            ['limit' => 24000, 'rate' => 0.10],
            ['limit' => 8333, 'rate' => 0.25],
            ['limit' => 467667, 'rate' => 0.30],
            ['limit' => 300000, 'rate' => 0.325],
            ['limit' => null, 'rate' => 0.35],
        ];

        $remaining = $taxableIncome;
        $tax = 0;

        foreach ($bands as $band) {
            $limit = $band['limit'] ?? null;
            $rate = (float) ($band['rate'] ?? 0);
            if ($remaining <= 0) {
                break;
            }
            $amount = $limit ? min($remaining, $limit) : $remaining;
            $tax += $amount * $rate;
            $remaining -= $amount;
        }

        return round($tax, 2);
    }

    protected function resolveTaxProfile(?int $taxProfileId, ?Payroll $payroll = null): ?TaxProfile
    {
        if ($taxProfileId) {
            return TaxProfile::find($taxProfileId);
        }

        if ($payroll && $payroll->taxProfile) {
            return $payroll->taxProfile;
        }

        return TaxProfile::first();
    }

    protected function computePayrollFields(array $input, ?TaxProfile $profile, ?Payroll $existing = null): array
    {
        $basic = (float) ($input['basic_salary'] ?? $existing?->basic_salary ?? 0);
        $allowances = (float) ($input['allowances'] ?? $existing?->allowances ?? 0);
        $gross = $basic + $allowances;

        $nssf = $this->calculateNssf($gross, $profile);
        $shifRate = (float) ($profile?->shif_rate ?? 0.0275);
        $shifMin = (float) ($profile?->shif_min ?? 300);
        $housingRate = (float) ($profile?->housing_levy_rate ?? 0.015);
        $shif = max(round($gross * $shifRate, 2), $shifMin);
        $housingLevy = round($gross * $housingRate, 2);

        $pensionCap = (float) ($profile?->pension_cap ?? 30000);
        $mortgageCap = (float) ($profile?->mortgage_cap ?? 30000);
        $pension = min((float) ($input['pension_contribution'] ?? $existing?->pension_contribution ?? 0), $pensionCap);
        $mortgage = min((float) ($input['mortgage_interest'] ?? $existing?->mortgage_interest ?? 0), $mortgageCap);
        $insurancePremium = (float) ($input['insurance_premium'] ?? $existing?->insurance_premium ?? 0);

        $taxableIncome = max($gross - $nssf - $shif - $housingLevy - $pension - $mortgage, 0);
        $taxBeforeRelief = $this->calculatePaye($taxableIncome, $profile);
        $personalRelief = (float) ($profile?->personal_relief ?? 2400);
        $insuranceReliefRate = (float) ($profile?->insurance_relief_rate ?? 0.15);
        $insuranceReliefCap = (float) ($profile?->insurance_relief_cap ?? 5000);
        $insuranceRelief = min(round($insurancePremium * $insuranceReliefRate, 2), $insuranceReliefCap);
        $paye = max($taxBeforeRelief - $personalRelief - $insuranceRelief, 0);
        $taxRate = $taxableIncome > 0 ? round(($paye / $taxableIncome) * 100, 2) : 0;

        $otherDeductions = (float) ($input['deductions'] ?? $existing?->deductions ?? 0);
        $netSalary = $gross - ($nssf + $shif + $housingLevy + $paye + $otherDeductions);

        return [
            'tax_profile_id' => $profile?->id,
            'basic_salary' => $basic,
            'allowances' => $allowances,
            'gross_salary' => $gross,
            'nssf' => $nssf,
            'shif' => $shif,
            'housing_levy' => $housingLevy,
            'taxable_income' => $taxableIncome,
            'paye' => $paye,
            'tax_rate' => $taxRate,
            'personal_relief' => $personalRelief,
            'insurance_premium' => $insurancePremium,
            'insurance_relief' => $insuranceRelief,
            'pension_contribution' => $pension,
            'mortgage_interest' => $mortgage,
            'deductions' => $otherDeductions,
            'tax' => $paye,
            'net_salary' => $netSalary,
        ];
    }

    #[OA\Get(
        path: "/api/payrolls",
        operationId: "getPayrolls",
        description: "Get list of all payrolls",
        summary: "List all payrolls",
        tags: ["Payrolls"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Payrolls retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: ""),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer"),
                                    new OA\Property(property: "employee_id", type: "integer"),
                                    new OA\Property(property: "month", type: "integer"),
                                    new OA\Property(property: "year", type: "integer"),
                                    new OA\Property(property: "basic_salary", type: "number", format: "float"),
                                    new OA\Property(property: "allowances", type: "number", format: "float"),
                                    new OA\Property(property: "deductions", type: "number", format: "float"),
                                    new OA\Property(property: "tax", type: "number", format: "float"),
                                    new OA\Property(property: "net_salary", type: "number", format: "float"),
                                    new OA\Property(property: "created_at", type: "string", format: "date-time"),
                                    new OA\Property(property: "updated_at", type: "string", format: "date-time")
                                ]
                            )
                        )
                    ]
                )
            )
        ]
    )]
    public function index()
    {
        $perPage = max((int) request()->query('per_page', 10), 1);
        $payrolls = Payroll::with('employee', 'payslip')->paginate($perPage);
        return ApiResponse::success(PayrollResource::collection($payrolls));   
    }

    #[OA\Post(
        path: "/api/payrolls",
        operationId: "storePayroll",
        description: "Create a new payroll",
        summary: "Create payroll",
        tags: ["Payrolls"],
        requestBody: new OA\RequestBody(
            description: "Payroll data",
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["employee_id", "month", "year", "basic_salary"],
                properties: [
                    new OA\Property(property: "employee_id", type: "integer", description: "Employee ID", example: 1),
                    new OA\Property(property: "month", type: "integer", description: "Month (1-12)", example: 3),
                    new OA\Property(property: "year", type: "integer", description: "Year", example: 2026),
                    new OA\Property(property: "basic_salary", type: "number", format: "float", description: "Basic salary", example: 5000.00),
                    new OA\Property(property: "allowances", type: "number", format: "float", description: "Allowances", example: 1000.00, nullable: true),
                    new OA\Property(property: "deductions", type: "number", format: "float", description: "Deductions", example: 500.00, nullable: true),
                    new OA\Property(property: "tax", type: "number", format: "float", description: "Tax", example: 300.00, nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Payroll created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Payroll created successfully"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'tax_profile_id' => 'nullable|exists:tax_profiles,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer',
            'basic_salary' => 'required|numeric',
            'allowances' => 'numeric|nullable',
            'deductions' => 'numeric|nullable',
            'tax' => 'numeric|nullable'
            ,
            'insurance_premium' => 'numeric|nullable',
            'pension_contribution' => 'numeric|nullable',
            'mortgage_interest' => 'numeric|nullable'
        ]);

        $profile = $this->resolveTaxProfile($validated['tax_profile_id'] ?? null);
        $computed = $this->computePayrollFields($validated, $profile);

        $payload = array_merge([
            'employee_id' => $validated['employee_id'],
            'month' => $validated['month'],
            'year' => $validated['year'],
        ], $computed);

        $payload['status'] = 'draft';
        $payroll = Payroll::create($payload);

        return ApiResponse::success(new PayrollResource($payroll), "Payroll created successfully", 201);
    }

    #[OA\Get(
        path: "/api/payrolls/{id}",
        operationId: "getPayroll",
        description: "Get a specific payroll",
        summary: "Get payroll by ID",
        tags: ["Payrolls"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Payroll ID",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Payroll retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: ""),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Payroll not found")
        ]
    )]
    public function show(Payroll $payroll)
    {
        return ApiResponse::success(new PayrollResource($payroll->load('employee', 'payslip')));
    }

    #[OA\Put(
        path: "/api/payrolls/{id}",
        operationId: "updatePayroll",
        description: "Update a payroll",
        summary: "Update payroll",
        tags: ["Payrolls"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Payroll ID",
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            description: "Payroll data",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "basic_salary", type: "number", format: "float", description: "Basic salary", example: 5000.00, nullable: true),
                    new OA\Property(property: "allowances", type: "number", format: "float", description: "Allowances", example: 1000.00, nullable: true),
                    new OA\Property(property: "deductions", type: "number", format: "float", description: "Deductions", example: 500.00, nullable: true),
                    new OA\Property(property: "tax", type: "number", format: "float", description: "Tax", example: 300.00, nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Payroll updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Payroll updated successfully"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Payroll not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(Request $request, Payroll $payroll)
    {
        $validated = $request->validate([
            'basic_salary' => 'numeric|nullable',
            'allowances' => 'numeric|nullable',
            'deductions' => 'numeric|nullable',
            'tax' => 'numeric|nullable',
            'insurance_premium' => 'numeric|nullable',
            'pension_contribution' => 'numeric|nullable',
            'mortgage_interest' => 'numeric|nullable',
            'tax_profile_id' => 'nullable|exists:tax_profiles,id',
        ]);

        $profile = $this->resolveTaxProfile($validated['tax_profile_id'] ?? null, $payroll);
        $computed = $this->computePayrollFields($validated, $profile, $payroll);

        $payroll->update(array_merge($computed, [
            'status' => 'draft',
            'approved_by' => null,
            'approved_at' => null,
            'approver_signature_name' => null,
            'approver_signature_at' => null,
            'approver_signature_ip' => null,
            'approver_signature_user_agent' => null,
        ]));

        $this->payslipService->syncPayslipForPayroll($payroll->load('employee', 'payslip'));

        return ApiResponse::success(new PayrollResource($payroll), "Payroll updated successfully");
    }

    #[OA\Delete(
        path: "/api/payrolls/{id}",
        operationId: "destroyPayroll",
        description: "Delete a payroll",
        summary: "Delete payroll",
        tags: ["Payrolls"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Payroll ID",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Payroll deleted successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Payroll deleted successfully"),
                        new OA\Property(property: "data", type: "null")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Payroll not found")
        ]
    )]
    public function destroy(Payroll $payroll)
    {
        $payroll->delete();
        return ApiResponse::success(null, "Payroll deleted successfully");
    }

    public function approve(Request $request, Payroll $payroll)
    {
        $validated = $request->validate([
            'signature_name' => 'required|string|max:255',
        ]);

        $user = $request->user();
        if (!$user) {
            return ApiResponse::unauthorized('Unauthorized');
        }

        $payroll->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'approver_signature_name' => $validated['signature_name'],
            'approver_signature_at' => now(),
            'approver_signature_ip' => (string) $request->ip(),
            'approver_signature_user_agent' => substr((string) $request->userAgent(), 0, 512),
        ]);

        $payslip = $this->payslipService->createPayslip(['payroll_id' => $payroll->id]);
        if ($payslip && $payslip->status !== 'approved' && $payslip->status !== 'issued') {
            $this->payslipService->approvePayslip($payslip->id, $user->id);
        }

        app(\App\Services\ActivityLogService::class)->log(
            'payroll.approved',
            (int) $user->company_id,
            Payroll::class,
            $payroll->id,
            [
                'payroll_id' => $payroll->id,
                'approved_by' => $user->id,
                'signature_name' => $validated['signature_name'],
            ]
        );

        return ApiResponse::success(
            new PayrollResource($payroll->fresh(['employee', 'payslip'])),
            'Payroll approved and converted to payslip'
        );
    }

    #[OA\Get(
        path: "/api/payrolls/export/csv",
        operationId: "exportPayrollsToCSV",
        description: "Export payrolls to CSV",
        summary: "Export payrolls to CSV",
        tags: ["Payrolls"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Payrolls exported successfully",
                content: new OA\MediaType(
                    mediaType: "text/csv",
                    schema: new OA\Schema(type: "string", format: "binary")
                )
            )
        ]
    )]
    public function exportToCSV()
    {
        $payrolls = Payroll::with('employee')->get();
        $header = [
            'employee_id',
            'employee_number',
            'employee_name',
            'employee_email',
            'month',
            'year',
            'basic_salary',
            'allowances',
            'gross_salary',
            'nssf',
            'shif',
            'housing_levy',
            'taxable_income',
            'paye',
            'tax_rate',
            'personal_relief',
            'insurance_premium',
            'insurance_relief',
            'pension_contribution',
            'mortgage_interest',
            'deductions',
            'tax',
            'net_salary',
            'tax_profile_id',
        ];

        $csv = \App\Helpers\CsvHelper::row($header);

        foreach ($payrolls as $payroll) {
            $employee = $payroll->employee;
            $csv .= \App\Helpers\CsvHelper::row([
                $payroll->employee_id,
                $employee?->employee_number,
                $employee?->name,
                $employee?->email,
                $payroll->month,
                $payroll->year,
                $payroll->basic_salary,
                $payroll->allowances,
                $payroll->gross_salary,
                $payroll->nssf,
                $payroll->shif,
                $payroll->housing_levy,
                $payroll->taxable_income,
                $payroll->paye,
                $payroll->tax_rate,
                $payroll->personal_relief,
                $payroll->insurance_premium,
                $payroll->insurance_relief,
                $payroll->pension_contribution,
                $payroll->mortgage_interest,
                $payroll->deductions,
                $payroll->tax,
                $payroll->net_salary,
                $payroll->tax_profile_id,
            ]);
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="payrolls.csv"');
    }

    #[OA\Post(
        path: "/api/payrolls/import/csv",
        operationId: "importPayrollsFromCSV",
        description: "Import payrolls from CSV",
        summary: "Import payrolls from CSV",
        tags: ["Payrolls"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Payrolls imported successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Import complete"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function importFromCSV(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,txt',
            'mode' => 'nullable|in:upsert,skip',
        ]);

        $mode = $validated['mode'] ?? 'upsert';
        $path = $request->file('file')->getRealPath();
        $csv = new \SplFileObject($path);
        $csv->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);

        $header = null;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];
        $rowNumber = 0;

        foreach ($csv as $row) {
            $rowNumber++;
            if ($row === [null] || $row === false) {
                continue;
            }

            if ($header === null) {
                $header = array_map(fn ($value) => \App\Helpers\CsvHelper::normalizeHeader((string) $value), $row);
                continue;
            }

            $data = [];
            foreach ($header as $index => $key) {
                if ($key === '') {
                    continue;
                }
                $data[$key] = isset($row[$index]) ? trim((string) $row[$index]) : null;
            }

            $hasContent = collect($data)->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty();
            if (!$hasContent) {
                continue;
            }

            try {
                $employeeId = $data['employee_id'] ?? null;
                if (!$employeeId && !empty($data['employee_email'])) {
                    $employeeId = \App\Models\Employee::where('email', $data['employee_email'])->value('id');
                }
                if (!$employeeId && !empty($data['employee_number'])) {
                    $employeeId = \App\Models\Employee::where('employee_number', $data['employee_number'])->value('id');
                }

                if (!$employeeId) {
                    throw new \Exception('Employee not found');
                }

                $month = $data['month'] ?? null;
                $year = $data['year'] ?? null;
                $basicSalary = $data['basic_salary'] ?? null;

                if (!$month || !$year || $basicSalary === null || $basicSalary === '') {
                    throw new \Exception('Missing required fields (month, year, basic_salary)');
                }

                $input = [
                    'employee_id' => (int) $employeeId,
                    'month' => (int) $month,
                    'year' => (int) $year,
                    'basic_salary' => (float) $basicSalary,
                    'allowances' => $data['allowances'] ?? null,
                    'deductions' => $data['deductions'] ?? null,
                    'tax_profile_id' => $data['tax_profile_id'] ?? null,
                    'insurance_premium' => $data['insurance_premium'] ?? null,
                    'pension_contribution' => $data['pension_contribution'] ?? null,
                    'mortgage_interest' => $data['mortgage_interest'] ?? null,
                ];

                $existing = Payroll::where('employee_id', $input['employee_id'])
                    ->where('month', $input['month'])
                    ->where('year', $input['year'])
                    ->first();

                if ($existing) {
                    if ($mode === 'skip') {
                        $skipped++;
                        continue;
                    }
                    $profile = $this->resolveTaxProfile(
                        isset($input['tax_profile_id']) ? (int) $input['tax_profile_id'] : null,
                        $existing
                    );
                    $computed = $this->computePayrollFields($input, $profile, $existing);
                    $existing->update(array_merge($computed, [
                        'status' => 'draft',
                        'approved_by' => null,
                        'approved_at' => null,
                        'approver_signature_name' => null,
                        'approver_signature_at' => null,
                        'approver_signature_ip' => null,
                        'approver_signature_user_agent' => null,
                    ]));
                    $this->payslipService->syncPayslipForPayroll($existing->load('employee', 'payslip'));
                    $updated++;
                } else {
                    $profile = $this->resolveTaxProfile(isset($input['tax_profile_id']) ? (int) $input['tax_profile_id'] : null);
                    $computed = $this->computePayrollFields($input, $profile);
                    $payload = array_merge([
                        'employee_id' => $input['employee_id'],
                        'month' => $input['month'],
                        'year' => $input['year'],
                        'status' => 'draft',
                    ], $computed);
                    Payroll::create($payload);
                    $created++;
                }
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = [
                    'row' => $rowNumber,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return ApiResponse::success([
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'failed' => $failed,
            'errors' => $errors,
        ], 'Import complete');
    }

}

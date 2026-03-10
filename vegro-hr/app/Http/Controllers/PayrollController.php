<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\TaxProfile;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Resources\PayrollResource;
use OpenApi\Attributes as OA;

#[OA\Info(title: "Payroll API", version: "1.0.0")]

class PayrollController extends Controller
{
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

        $profile = isset($validated['tax_profile_id'])
            ? TaxProfile::find($validated['tax_profile_id'])
            : TaxProfile::first();

        $basic = (float) $validated['basic_salary'];
        $allowances = (float) ($validated['allowances'] ?? 0);
        $gross = $basic + $allowances;

        $nssf = $this->calculateNssf($gross, $profile);
        $shifRate = (float) ($profile?->shif_rate ?? 0.0275);
        $shifMin = (float) ($profile?->shif_min ?? 300);
        $housingRate = (float) ($profile?->housing_levy_rate ?? 0.015);
        $shif = max(round($gross * $shifRate, 2), $shifMin);
        $housingLevy = round($gross * $housingRate, 2);

        $pensionCap = (float) ($profile?->pension_cap ?? 30000);
        $mortgageCap = (float) ($profile?->mortgage_cap ?? 30000);
        $pension = min((float) ($validated['pension_contribution'] ?? 0), $pensionCap);
        $mortgage = min((float) ($validated['mortgage_interest'] ?? 0), $mortgageCap);
        $insurancePremium = (float) ($validated['insurance_premium'] ?? 0);

        $taxableIncome = max($gross - $nssf - $shif - $housingLevy - $pension - $mortgage, 0);
        $taxBeforeRelief = $this->calculatePaye($taxableIncome, $profile);
        $personalRelief = (float) ($profile?->personal_relief ?? 2400);
        $insuranceReliefRate = (float) ($profile?->insurance_relief_rate ?? 0.15);
        $insuranceReliefCap = (float) ($profile?->insurance_relief_cap ?? 5000);
        $insuranceRelief = min(round($insurancePremium * $insuranceReliefRate, 2), $insuranceReliefCap);
        $paye = max($taxBeforeRelief - $personalRelief - $insuranceRelief, 0);
        $taxRate = $taxableIncome > 0 ? round(($paye / $taxableIncome) * 100, 2) : 0;

        $otherDeductions = (float) ($validated['deductions'] ?? 0);
        $netSalary = $gross - ($nssf + $shif + $housingLevy + $paye + $otherDeductions);

        $validated['gross_salary'] = $gross;
        $validated['nssf'] = $nssf;
        $validated['shif'] = $shif;
        $validated['housing_levy'] = $housingLevy;
        $validated['taxable_income'] = $taxableIncome;
        $validated['paye'] = $paye;
        $validated['tax_rate'] = $taxRate;
        $validated['personal_relief'] = $personalRelief;
        $validated['insurance_premium'] = $insurancePremium;
        $validated['insurance_relief'] = $insuranceRelief;
        $validated['pension_contribution'] = $pension;
        $validated['mortgage_interest'] = $mortgage;
        $validated['tax'] = $paye;
        $validated['net_salary'] = $netSalary;

        $payroll = Payroll::create($validated);

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

        $profile = isset($validated['tax_profile_id'])
            ? TaxProfile::find($validated['tax_profile_id'])
            : ($payroll->taxProfile ?: TaxProfile::first());

        $basic = (float) ($validated['basic_salary'] ?? $payroll->basic_salary);
        $allowances = (float) ($validated['allowances'] ?? $payroll->allowances);
        $gross = $basic + $allowances;

        $nssf = $this->calculateNssf($gross, $profile);
        $shifRate = (float) ($profile?->shif_rate ?? 0.0275);
        $shifMin = (float) ($profile?->shif_min ?? 300);
        $housingRate = (float) ($profile?->housing_levy_rate ?? 0.015);
        $shif = max(round($gross * $shifRate, 2), $shifMin);
        $housingLevy = round($gross * $housingRate, 2);

        $pensionCap = (float) ($profile?->pension_cap ?? 30000);
        $mortgageCap = (float) ($profile?->mortgage_cap ?? 30000);
        $pension = min((float) ($validated['pension_contribution'] ?? $payroll->pension_contribution), $pensionCap);
        $mortgage = min((float) ($validated['mortgage_interest'] ?? $payroll->mortgage_interest), $mortgageCap);
        $insurancePremium = (float) ($validated['insurance_premium'] ?? $payroll->insurance_premium);

        $taxableIncome = max($gross - $nssf - $shif - $housingLevy - $pension - $mortgage, 0);
        $taxBeforeRelief = $this->calculatePaye($taxableIncome, $profile);
        $personalRelief = (float) ($profile?->personal_relief ?? 2400);
        $insuranceReliefRate = (float) ($profile?->insurance_relief_rate ?? 0.15);
        $insuranceReliefCap = (float) ($profile?->insurance_relief_cap ?? 5000);
        $insuranceRelief = min(round($insurancePremium * $insuranceReliefRate, 2), $insuranceReliefCap);
        $paye = max($taxBeforeRelief - $personalRelief - $insuranceRelief, 0);
        $taxRate = $taxableIncome > 0 ? round(($paye / $taxableIncome) * 100, 2) : 0;

        $otherDeductions = (float) ($validated['deductions'] ?? $payroll->deductions);
        $netSalary = $gross - ($nssf + $shif + $housingLevy + $paye + $otherDeductions);

        $payroll->update([
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
            'net_salary' => $netSalary
        ]);

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

}

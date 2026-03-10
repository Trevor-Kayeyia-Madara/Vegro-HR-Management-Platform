<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\TaxProfile;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class TaxProfileController extends Controller
{
    #[OA\Get(
        path: "/api/tax-profiles",
        operationId: "listTaxProfiles",
        description: "Get all tax profiles",
        summary: "List all tax profiles",
        tags: ["Tax Profiles"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Tax profiles retrieved successfully"),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function index()
    {
        $perPage = max((int) request()->query('per_page', 10), 1);
        return ApiResponse::success(TaxProfile::orderBy('created_at', 'desc')->paginate($perPage), "Tax profiles retrieved successfully");
    }

    #[OA\Post(
        path: "/api/tax-profiles",
        operationId: "createTaxProfile",
        description: "Create a new tax profile",
        summary: "Create tax profile",
        tags: ["Tax Profiles"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            description: "Tax profile data",
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["name", "country_code", "currency", "paye_bands"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Kenya PAYE (Monthly)"),
                    new OA\Property(property: "country_code", type: "string", example: "KE"),
                    new OA\Property(property: "currency", type: "string", example: "KES"),
                    new OA\Property(property: "paye_bands", type: "array", items: new OA\Items(type: "object")),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Tax profile created successfully"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'country_code' => 'required|string|size:2',
            'currency' => 'required|string|size:3',
            'paye_bands' => 'required|array',
            'personal_relief' => 'numeric|nullable',
            'insurance_relief_rate' => 'numeric|nullable',
            'insurance_relief_cap' => 'numeric|nullable',
            'pension_cap' => 'numeric|nullable',
            'mortgage_cap' => 'numeric|nullable',
            'nssf_rate' => 'numeric|nullable',
            'nssf_tier1_limit' => 'numeric|nullable',
            'nssf_tier2_limit' => 'numeric|nullable',
            'nssf_max' => 'numeric|nullable',
            'shif_rate' => 'numeric|nullable',
            'shif_min' => 'numeric|nullable',
            'housing_levy_rate' => 'numeric|nullable',
        ]);

        $profile = TaxProfile::create($validated);

        return ApiResponse::success($profile, "Tax profile created successfully", 201);
    }

    #[OA\Get(
        path: "/api/tax-profiles/{taxProfile}",
        operationId: "showTaxProfile",
        description: "Get a specific tax profile",
        summary: "Get tax profile",
        tags: ["Tax Profiles"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "taxProfile",
                in: "path",
                required: true,
                description: "Tax profile ID",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Tax profile retrieved successfully"),
            new OA\Response(response: 404, description: "Tax profile not found")
        ]
    )]
    public function show(TaxProfile $taxProfile)
    {
        return ApiResponse::success($taxProfile, "Tax profile retrieved successfully");
    }

    #[OA\Put(
        path: "/api/tax-profiles/{taxProfile}",
        operationId: "updateTaxProfile",
        description: "Update a tax profile",
        summary: "Update tax profile",
        tags: ["Tax Profiles"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            description: "Tax profile data",
            content: new OA\JsonContent(type: "object")
        ),
        responses: [
            new OA\Response(response: 200, description: "Tax profile updated successfully"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(Request $request, TaxProfile $taxProfile)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'country_code' => 'sometimes|required|string|size:2',
            'currency' => 'sometimes|required|string|size:3',
            'paye_bands' => 'sometimes|required|array',
            'personal_relief' => 'numeric|nullable',
            'insurance_relief_rate' => 'numeric|nullable',
            'insurance_relief_cap' => 'numeric|nullable',
            'pension_cap' => 'numeric|nullable',
            'mortgage_cap' => 'numeric|nullable',
            'nssf_rate' => 'numeric|nullable',
            'nssf_tier1_limit' => 'numeric|nullable',
            'nssf_tier2_limit' => 'numeric|nullable',
            'nssf_max' => 'numeric|nullable',
            'shif_rate' => 'numeric|nullable',
            'shif_min' => 'numeric|nullable',
            'housing_levy_rate' => 'numeric|nullable',
        ]);

        $taxProfile->update($validated);

        return ApiResponse::success($taxProfile, "Tax profile updated successfully");
    }

    #[OA\Delete(
        path: "/api/tax-profiles/{taxProfile}",
        operationId: "deleteTaxProfile",
        description: "Delete a tax profile",
        summary: "Delete tax profile",
        tags: ["Tax Profiles"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Tax profile deleted successfully")
        ]
    )]
    public function destroy(TaxProfile $taxProfile)
    {
        $taxProfile->delete();
        return ApiResponse::success(null, "Tax profile deleted successfully");
    }
}

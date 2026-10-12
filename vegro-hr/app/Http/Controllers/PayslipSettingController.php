<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\CompanySetting;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PayslipSettingController extends Controller
{
    /**
     * Return the current company's payslip presentation settings.
     *
     * Settings intentionally live in CompanySetting::branding so they remain
     * tenant-scoped and can be extended without a separate settings table.
     */
    public function index(Request $request)
    {
        $settings = $this->settingsFor($request);
        if (!$settings) {
            return ApiResponse::forbidden('Company context is unavailable.');
        }

        return ApiResponse::success(
            $this->payslipSettings($settings),
            'Payslip settings retrieved successfully'
        );
    }

    /**
     * Update the JSON layout used by the payslip renderer.
     *
     * The layout is deliberately an open JSON object: frontend layout editors
     * can evolve their sections, positioning, typography, and footer fields
     * without requiring a backend schema change.
     */
    public function update(Request $request)
    {
        $settings = $this->settingsFor($request);
        if (!$settings) {
            return ApiResponse::forbidden('Company context is unavailable.');
        }

        $validated = $request->validate([
            'layout' => ['sometimes', 'required', 'array'],
            'layout.page' => ['sometimes', 'array'],
            'layout.header' => ['sometimes', 'array'],
            'layout.sections' => ['sometimes', 'array'],
            'layout.footer' => ['sometimes', 'array'],
            'layout.theme' => ['sometimes', 'array'],
            'show_logo' => ['sometimes', 'boolean'],
            'document_title' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $branding = $settings->branding ?? [];
        if (array_key_exists('layout', $validated)) {
            $branding['payslip_layout'] = $validated['layout'];
        }
        if (array_key_exists('show_logo', $validated)) {
            $branding['payslip_show_logo'] = $validated['show_logo'];
        }
        if (array_key_exists('document_title', $validated)) {
            $branding['payslip_document_title'] = $validated['document_title'];
        }

        $settings->update(['branding' => $branding]);

        app(ActivityLogService::class)->log(
            'payslip.settings.updated',
            (int) $settings->company_id,
            CompanySetting::class,
            $settings->id,
            ['updated_fields' => array_keys($validated)]
        );

        return ApiResponse::success(
            $this->payslipSettings($settings->fresh()),
            'Payslip settings updated successfully'
        );
    }

    /** Upload or replace the logo rendered in a payslip header. */
    public function uploadLogo(Request $request)
    {
        $settings = $this->settingsFor($request);
        if (!$settings) {
            return ApiResponse::forbidden('Company context is unavailable.');
        }

        $request->validate([
            'logo' => ['required', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        $branding = $settings->branding ?? [];
        $previousPath = $branding['payslip_logo_path'] ?? null;
        $path = $request->file('logo')->store("payslips/{$settings->company_id}/branding", 'public');

        if ($previousPath && $previousPath !== $path) {
            Storage::disk('public')->delete($previousPath);
        }

        $branding['payslip_logo_path'] = $path;
        $branding['payslip_logo_name'] = $request->file('logo')->getClientOriginalName();
        $settings->update(['branding' => $branding]);

        app(ActivityLogService::class)->log(
            'payslip.logo.uploaded',
            (int) $settings->company_id,
            CompanySetting::class,
            $settings->id
        );

        return ApiResponse::success(
            $this->payslipSettings($settings->fresh()),
            'Payslip logo uploaded successfully'
        );
    }

    /** Remove the configured payslip logo without touching other branding. */
    public function deleteLogo(Request $request)
    {
        $settings = $this->settingsFor($request);
        if (!$settings) {
            return ApiResponse::forbidden('Company context is unavailable.');
        }

        $branding = $settings->branding ?? [];
        $path = $branding['payslip_logo_path'] ?? null;
        if ($path) {
            Storage::disk('public')->delete($path);
        }

        unset($branding['payslip_logo_path'], $branding['payslip_logo_name']);
        $settings->update(['branding' => $branding]);

        app(ActivityLogService::class)->log(
            'payslip.logo.deleted',
            (int) $settings->company_id,
            CompanySetting::class,
            $settings->id
        );

        return ApiResponse::success(
            $this->payslipSettings($settings->fresh()),
            'Payslip logo deleted successfully'
        );
    }

    private function settingsFor(Request $request): ?CompanySetting
    {
        $companyId = $request->attributes->get('company_id') ?? $request->user()?->company_id;
        if (!$companyId) {
            return null;
        }

        return CompanySetting::firstOrCreate(['company_id' => $companyId]);
    }

    private function payslipSettings(CompanySetting $settings): array
    {
        $branding = $settings->branding ?? [];
        $logoPath = $branding['payslip_logo_path'] ?? null;

        return [
            'layout' => $branding['payslip_layout'] ?? $this->defaultLayout(),
            'document_title' => $branding['payslip_document_title'] ?? 'Payslip',
            'show_logo' => $branding['payslip_show_logo'] ?? true,
            'logo_name' => $branding['payslip_logo_name'] ?? null,
            'logo_url' => $logoPath ? Storage::disk('public')->url($logoPath) : null,
        ];
    }

    private function defaultLayout(): array
    {
        return [
            'page' => ['size' => 'A4', 'orientation' => 'portrait', 'margin' => 24],
            'header' => ['company_details' => true, 'employee_details' => true],
            'sections' => ['earnings' => true, 'deductions' => true, 'summary' => true],
            'footer' => ['show_generated_at' => true],
            'theme' => ['primary_color' => '#1f2937', 'accent_color' => '#2563eb'],
        ];
    }
}

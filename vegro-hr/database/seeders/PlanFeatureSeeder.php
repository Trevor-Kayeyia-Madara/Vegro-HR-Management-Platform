<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\Plan;
use App\Models\PlanFeature;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class PlanFeatureSeeder extends Seeder
{
    public function run(): void
    {
        $features = [
            ['name' => 'Core HR', 'key' => 'core_hr'],
            ['name' => 'Employee Management', 'key' => 'employee_management'],
            ['name' => 'Departments & Roles', 'key' => 'departments_roles'],
            ['name' => 'Payroll Basic', 'key' => 'payroll_basic'],
            ['name' => 'Leave Management', 'key' => 'leave_management'],
            ['name' => 'Attendance Tracking', 'key' => 'attendance_tracking'],
            ['name' => 'Employee Self Service', 'key' => 'employee_self_service'],
            ['name' => 'Basic Dashboard', 'key' => 'basic_dashboard'],
            ['name' => 'CSV Import Export', 'key' => 'csv_import_export'],
            ['name' => 'ATS Recruitment', 'key' => 'ats_recruitment'],
            ['name' => 'Document Signing', 'key' => 'document_signing'],
            ['name' => 'Compliance Alerts', 'key' => 'compliance_alerts'],
            ['name' => 'Advanced Audit Logs', 'key' => 'advanced_audit_logs'],
            ['name' => 'Multi Manager Org', 'key' => 'multi_manager_org'],
            ['name' => 'Report Builder Basic', 'key' => 'report_builder_basic'],
            ['name' => 'Report Builder Advanced', 'key' => 'report_builder_advanced'],
            ['name' => 'Custom Fields', 'key' => 'custom_fields'],
            ['name' => 'Advanced Payroll Rules', 'key' => 'advanced_payroll_rules'],
            ['name' => 'Analytics Dashboard', 'key' => 'analytics_dashboard'],
            ['name' => 'API Access', 'key' => 'api_access'],
            ['name' => 'Priority Support', 'key' => 'priority_support'],
            ['name' => 'Webhooks', 'key' => 'webhooks'],
            ['name' => 'External Integrations', 'key' => 'external_integrations'],
            ['name' => 'Dedicated Infrastructure', 'key' => 'dedicated_infrastructure'],
            ['name' => 'White Labeling', 'key' => 'white_labeling'],
            ['name' => 'SLA Support', 'key' => 'sla_support'],
            ['name' => 'Multi Environment Control', 'key' => 'multi_environment_control'],
        ];

        foreach ($features as $feature) {
            Feature::updateOrCreate(
                ['key' => $feature['key']],
                [
                    'name' => $feature['name'],
                    'description' => $feature['description'] ?? null,
                ]
            );
        }

        $starterKeys = [
            'core_hr',
            'employee_management',
            'departments_roles',
            'payroll_basic',
            'leave_management',
            'attendance_tracking',
            'employee_self_service',
            'basic_dashboard',
            'csv_import_export',
        ];

        $growthKeys = array_merge($starterKeys, [
            'ats_recruitment',
            'document_signing',
            'compliance_alerts',
            'advanced_audit_logs',
            'multi_manager_org',
            'report_builder_basic',
        ]);

        $proKeys = array_merge($growthKeys, [
            'report_builder_advanced',
            'custom_fields',
            'advanced_payroll_rules',
            'analytics_dashboard',
            'api_access',
            'priority_support',
        ]);

        $enterpriseKeys = Feature::query()->pluck('key')->all();

        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'price_monthly' => 2,
                'price_annual' => 19,
                'employee_limit' => 30,
                'description' => 'HR Essentials',
                'legacy_features' => [
                    'audit_logs' => 'basic',
                    'report_builder' => false,
                ],
                'features' => $starterKeys,
                'config' => [
                    'audit_logs' => 'basic',
                    'report_builder' => false,
                ],
            ],
            [
                'name' => 'Growth',
                'slug' => 'growth',
                'price_monthly' => 4,
                'price_annual' => 38,
                'employee_limit' => 150,
                'description' => 'Operational Control',
                'legacy_features' => [
                    'report_builder' => [
                        'enabled' => true,
                        'max_saved_reports' => 3,
                        'cross_module' => false,
                        'advanced_filters' => false,
                        'export' => false,
                    ],
                ],
                'features' => $growthKeys,
                'config' => [
                    'report_builder' => [
                        'enabled' => true,
                        'max_saved_reports' => 3,
                        'cross_module' => false,
                        'advanced_filters' => false,
                        'export' => false,
                    ],
                ],
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'price_monthly' => 7,
                'price_annual' => 70,
                'employee_limit' => 500,
                'description' => 'Data & Intelligence',
                'legacy_features' => [
                    'report_builder' => [
                        'enabled' => true,
                        'max_saved_reports' => 999,
                        'cross_module' => true,
                        'advanced_filters' => true,
                        'export' => true,
                        'scheduled_reports' => true,
                    ],
                ],
                'features' => $proKeys,
                'config' => [
                    'report_builder' => [
                        'enabled' => true,
                        'max_saved_reports' => 999,
                        'cross_module' => true,
                        'advanced_filters' => true,
                        'export' => true,
                        'scheduled_reports' => true,
                    ],
                ],
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'price_monthly' => null,
                'price_annual' => null,
                'employee_limit' => null,
                'description' => 'Scale & Integration',
                'legacy_features' => [
                    'report_builder' => 'unlimited',
                ],
                'features' => $enterpriseKeys,
                'config' => [
                    'report_builder' => [
                        'mode' => 'unlimited',
                    ],
                ],
            ],
        ];

        foreach ($plans as $planPayload) {
            $plan = Plan::updateOrCreate(
                ['slug' => $planPayload['slug']],
                [
                    'name' => $planPayload['name'],
                    'slug' => $planPayload['slug'],
                    'price' => $planPayload['price_monthly'] ?? 0,
                    'currency' => 'USD',
                    'interval' => 'monthly',
                    'price_monthly' => $planPayload['price_monthly'],
                    'price_annual' => $planPayload['price_annual'],
                    'employee_limit' => $planPayload['employee_limit'],
                    'description' => $planPayload['description'],
                    'features' => $planPayload['legacy_features'],
                    'is_active' => true,
                ]
            );

            foreach ($planPayload['features'] as $key) {
                $feature = Feature::where('key', $key)->first();
                if (!$feature) {
                    continue;
                }

                PlanFeature::updateOrCreate(
                    [
                        'plan_id' => $plan->id,
                        'feature_id' => $feature->id,
                    ],
                    [
                        'config' => $this->featureConfig($key, $planPayload['config']),
                    ]
                );
            }
        }
    }

    protected function featureConfig(string $featureKey, array $planConfig): ?array
    {
        if (Str::startsWith($featureKey, 'report_builder')) {
            return Arr::get($planConfig, 'report_builder');
        }

        if ($featureKey === 'advanced_audit_logs' && isset($planConfig['audit_logs'])) {
            return ['level' => $planConfig['audit_logs']];
        }

        return null;
    }
}

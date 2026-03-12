<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $company = \App\Models\Company::updateOrCreate(
            ['domain' => 'default.local'],
            [
                'name' => 'Default Company',
                'status' => 'active',
                'plan' => 'starter',
                'environment' => 'demo',
            ]
        );

        \App\Models\CompanyDomain::firstOrCreate([
            'company_id' => $company->id,
            'domain' => 'default.local',
        ], [
            'is_primary' => true,
        ]);

        \App\Models\CompanySetting::firstOrCreate([
            'company_id' => $company->id,
        ], [
            'currency' => 'USD',
            'timezone' => 'UTC',
            'locale' => 'en',
        ]);
    }
}

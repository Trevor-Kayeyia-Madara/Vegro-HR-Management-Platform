<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('country_code', 2);
            $table->string('currency', 3)->default('KES');
            $table->json('paye_bands');
            $table->decimal('personal_relief', 10, 2)->default(0);
            $table->decimal('insurance_relief_rate', 5, 4)->default(0);
            $table->decimal('insurance_relief_cap', 10, 2)->default(0);
            $table->decimal('pension_cap', 10, 2)->default(0);
            $table->decimal('mortgage_cap', 10, 2)->default(0);
            $table->decimal('nssf_rate', 5, 4)->default(0);
            $table->decimal('nssf_tier1_limit', 10, 2)->default(0);
            $table->decimal('nssf_tier2_limit', 10, 2)->default(0);
            $table->decimal('nssf_max', 10, 2)->default(0);
            $table->decimal('shif_rate', 5, 4)->default(0);
            $table->decimal('shif_min', 10, 2)->default(0);
            $table->decimal('housing_levy_rate', 5, 4)->default(0);
            $table->timestamps();
        });

        DB::table('tax_profiles')->insert([
            'name' => 'Kenya PAYE (Monthly)',
            'country_code' => 'KE',
            'currency' => 'KES',
            'paye_bands' => json_encode([
                ['limit' => 24000, 'rate' => 0.10],
                ['limit' => 8333, 'rate' => 0.25],
                ['limit' => 467667, 'rate' => 0.30],
                ['limit' => 300000, 'rate' => 0.325],
                ['limit' => null, 'rate' => 0.35],
            ]),
            'personal_relief' => 2400,
            'insurance_relief_rate' => 0.15,
            'insurance_relief_cap' => 5000,
            'pension_cap' => 30000,
            'mortgage_cap' => 30000,
            'nssf_rate' => 0.06,
            'nssf_tier1_limit' => 9000,
            'nssf_tier2_limit' => 108000,
            'nssf_max' => 6480,
            'shif_rate' => 0.0275,
            'shif_min' => 300,
            'housing_levy_rate' => 0.015,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_profiles');
    }
};

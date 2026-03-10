<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->decimal('gross_salary', 10, 2)->default(0)->after('allowances');
            $table->decimal('nssf', 10, 2)->default(0)->after('gross_salary');
            $table->decimal('shif', 10, 2)->default(0)->after('nssf');
            $table->decimal('housing_levy', 10, 2)->default(0)->after('shif');
            $table->decimal('taxable_income', 10, 2)->default(0)->after('housing_levy');
            $table->decimal('paye', 10, 2)->default(0)->after('taxable_income');
            $table->decimal('tax_rate', 5, 2)->default(0)->after('paye');
            $table->decimal('personal_relief', 10, 2)->default(0)->after('tax_rate');
            $table->decimal('insurance_premium', 10, 2)->default(0)->after('personal_relief');
            $table->decimal('insurance_relief', 10, 2)->default(0)->after('insurance_premium');
            $table->decimal('pension_contribution', 10, 2)->default(0)->after('insurance_relief');
            $table->decimal('mortgage_interest', 10, 2)->default(0)->after('pension_contribution');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn([
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
            ]);
        });
    }
};

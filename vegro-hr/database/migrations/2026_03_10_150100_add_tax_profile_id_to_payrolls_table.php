<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->unsignedBigInteger('tax_profile_id')->nullable()->after('employee_id');
            $table->foreign('tax_profile_id')->references('id')->on('tax_profiles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropForeign(['tax_profile_id']);
            $table->dropColumn('tax_profile_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_profiles', function (Blueprint $table) {
            $table->string('base_currency', 3)->nullable()->after('currency');
            $table->decimal('exchange_rate_to_base', 18, 6)->nullable()->after('base_currency');
        });
    }

    public function down(): void
    {
        Schema::table('tax_profiles', function (Blueprint $table) {
            $table->dropColumn(['base_currency', 'exchange_rate_to_base']);
        });
    }
};


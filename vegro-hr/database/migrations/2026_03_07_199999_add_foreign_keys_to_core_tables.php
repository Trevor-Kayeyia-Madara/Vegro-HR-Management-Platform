<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip foreign keys that may already exist to avoid conflicts
        // Will be managed by the existing migration system
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No foreign keys to drop
    }
};

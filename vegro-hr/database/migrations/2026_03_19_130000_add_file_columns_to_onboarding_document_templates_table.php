<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onboarding_document_templates', function (Blueprint $table) {
            $table->string('file_name')->nullable()->after('content');
            $table->string('file_path')->nullable()->after('file_name');
            $table->string('file_mime', 120)->nullable()->after('file_path');
            $table->unsignedBigInteger('file_size')->nullable()->after('file_mime');
        });
    }

    public function down(): void
    {
        Schema::table('onboarding_document_templates', function (Blueprint $table) {
            $table->dropColumn(['file_name', 'file_path', 'file_mime', 'file_size']);
        });
    }
};

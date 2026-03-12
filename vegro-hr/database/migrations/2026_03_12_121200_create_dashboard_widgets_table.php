<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('dashboard_id')->index();
            $table->string('title');
            $table->string('source');
            $table->string('chart_type')->default('table');
            $table->json('columns')->nullable();
            $table->json('filters')->nullable();
            $table->json('sort')->nullable();
            $table->unsignedInteger('limit')->nullable();
            $table->string('x_field')->nullable();
            $table->string('y_field')->nullable();
            $table->string('aggregate')->nullable();
            $table->unsignedInteger('width')->default(6);
            $table->unsignedInteger('height')->default(4);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('dashboard_id')->references('id')->on('dashboard_definitions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_widgets');
    }
};

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
       Schema::create('payslips', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('payroll_id');
    $table->string('pdf_path')->nullable(); // Store PDF path
    $table->timestamp('generated_at')->nullable();
    $table->timestamps();

    $table->foreign('payroll_id')->references('id')->on('payrolls')->onDelete('cascade');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};

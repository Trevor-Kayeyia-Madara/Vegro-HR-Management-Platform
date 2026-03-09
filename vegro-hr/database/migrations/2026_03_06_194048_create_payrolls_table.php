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
       Schema::create('payrolls', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('employee_id');
    $table->tinyInteger('month');
    $table->smallInteger('year');
    $table->decimal('basic_salary', 10, 2);
    $table->decimal('allowances', 10, 2)->default(0);
    $table->decimal('deductions', 10, 2)->default(0);
    $table->decimal('tax', 10, 2)->default(0);
    $table->decimal('net_salary', 10, 2);
    $table->timestamps();

    $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};

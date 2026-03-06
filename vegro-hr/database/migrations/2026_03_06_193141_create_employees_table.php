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
        Schema::create('employees', function (Blueprint $table) {
        $table->id();
        $table->string('employee_number')->unique();
        $table->unsignedBigInteger('user_id')->nullable();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('phone')->nullable();
        $table->unsignedBigInteger('department_id')->nullable();
        $table->string('position');
        $table->decimal('salary', 10, 2);
        $table->date('hire_date');
        $table->enum('status', ['active','inactive'])->default('active');
        $table->timestamps();

        $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
});      
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};

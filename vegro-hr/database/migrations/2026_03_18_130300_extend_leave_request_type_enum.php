<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement(
                "ALTER TABLE leave_requests MODIFY type ENUM('annual','sick','emergency','maternity','paternity','adoptive','compassionate','public_holiday') NOT NULL"
            );
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement(
                "ALTER TABLE leave_requests MODIFY type ENUM('annual','sick','emergency') NOT NULL"
            );
        }
    }
};


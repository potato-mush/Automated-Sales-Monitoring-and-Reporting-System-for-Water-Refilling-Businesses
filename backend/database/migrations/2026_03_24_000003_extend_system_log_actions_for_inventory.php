<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE system_logs MODIFY action ENUM('login', 'logout', 'inventory_create', 'inventory_update', 'inventory_delete', 'inventory_adjust') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE system_logs MODIFY action ENUM('login', 'logout') NOT NULL");
    }
};

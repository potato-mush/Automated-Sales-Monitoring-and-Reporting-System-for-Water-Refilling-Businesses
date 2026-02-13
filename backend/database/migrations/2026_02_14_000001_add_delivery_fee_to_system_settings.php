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
        // Check if delivery_fee setting exists
        $exists = DB::table('system_settings')
            ->where('setting_key', 'delivery_fee')
            ->exists();

        if (!$exists) {
            DB::table('system_settings')->insert([
                'setting_key' => 'delivery_fee',
                'setting_value' => '50.00',
                'description' => 'Delivery fee for delivery transactions',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('system_settings')
            ->where('setting_key', 'delivery_fee')
            ->delete();
    }
};

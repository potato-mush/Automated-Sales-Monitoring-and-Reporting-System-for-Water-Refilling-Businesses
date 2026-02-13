<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key', 100)->unique();
            $table->text('setting_value');
            $table->text('description')->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        // Insert default settings
        DB::table('system_settings')->insert([
            [
                'setting_key' => 'gallon_price',
                'setting_value' => '25.00',
                'description' => 'Default price per gallon',
            ],
            [
                'setting_key' => 'delivery_fee',
                'setting_value' => '50.00',
                'description' => 'Delivery fee for delivery transactions',
            ],
            [
                'setting_key' => 'overdue_days_threshold',
                'setting_value' => '7',
                'description' => 'Days before gallon is considered overdue',
            ],
            [
                'setting_key' => 'missing_days_threshold',
                'setting_value' => '30',
                'description' => 'Days before gallon is marked as missing',
            ],
            [
                'setting_key' => 'business_name',
                'setting_value' => 'Water Refilling Station',
                'description' => 'Business name',
            ],
            [
                'setting_key' => 'business_address',
                'setting_value' => '',
                'description' => 'Business address',
            ],
            [
                'setting_key' => 'business_phone',
                'setting_value' => '',
                'description' => 'Business contact number',
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};

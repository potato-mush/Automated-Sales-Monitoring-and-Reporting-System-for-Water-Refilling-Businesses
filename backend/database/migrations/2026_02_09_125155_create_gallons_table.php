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
        Schema::create('gallons', function (Blueprint $table) {
            $table->id();
            $table->string('gallon_code', 50)->unique()->comment('QR Code unique identifier');
            $table->enum('status', ['IN', 'OUT', 'MISSING'])->default('IN');
            $table->unsignedBigInteger('last_transaction_id')->nullable();
            $table->dateTime('last_borrowed_date')->nullable();
            $table->dateTime('last_returned_date')->nullable();
            $table->boolean('is_overdue')->default(false);
            $table->integer('overdue_days')->default(0);
            $table->timestamps();
            
            $table->index('gallon_code');
            $table->index('status');
            $table->index('is_overdue');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gallons');
    }
};

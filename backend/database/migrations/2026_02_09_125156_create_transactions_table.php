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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_code', 50)->unique();
            $table->string('customer_name');
            $table->string('customer_phone', 20)->nullable();
            $table->text('customer_address')->nullable();
            $table->enum('transaction_type', ['walk-in', 'delivery', 'refill-only']);
            $table->enum('payment_method', ['cash', 'gcash', 'card', 'bank-transfer'])->default('cash');
            $table->integer('quantity')->default(0)->comment('Number of gallons');
            $table->decimal('unit_price', 10, 2)->default(0.00);
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->unsignedBigInteger('employee_id');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('transaction_code');
            $table->index('transaction_type');
            $table->index('employee_id');
            $table->index('created_at');
            
            $table->foreign('employee_id')->references('id')->on('users')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

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
        Schema::create('gallon_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('gallon_id');
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->enum('action', ['BORROW', 'RETURN', 'CREATED', 'STATUS_CHANGE']);
            $table->enum('old_status', ['IN', 'OUT', 'MISSING'])->nullable();
            $table->enum('new_status', ['IN', 'OUT', 'MISSING'])->nullable();
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('gallon_id');
            $table->index('transaction_id');
            $table->index('created_at');
            
            $table->foreign('gallon_id')->references('id')->on('gallons')->onDelete('cascade');
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('set null');
            $table->foreign('performed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gallon_logs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlement_clawbacks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('merchant_id');
            $table->uuid('original_redemption_id');
            $table->uuid('refund_transaction_id');
            $table->decimal('amount', 10, 2);
            $table->string('status');
            $table->uuid('settlement_id')->nullable();
            $table->timestamps();

            $table->foreign('merchant_id')->references('id')->on('merchants');
            $table->foreign('original_redemption_id')->references('id')->on('transactions');
            $table->foreign('refund_transaction_id')->references('id')->on('transactions');
            $table->foreign('settlement_id')->references('id')->on('settlements');

            $table->softDeletes();

            $table->index(['merchant_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlement_clawbacks');
    }
};

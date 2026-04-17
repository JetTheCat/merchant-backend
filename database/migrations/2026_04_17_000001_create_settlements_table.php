<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('merchant_id')->constrained()->onDelete('cascade');
            $table->string('reference_code', 20)->unique();
            $table->date('period_start');
            $table->date('period_end');

            $table->decimal('total_redemptions', 10, 2);
            $table->decimal('total_fees', 10, 2);
            $table->decimal('net_amount', 10, 2);
            $table->integer('redemption_count');

            $table->string('status', 20)->default('pending');

            $table->string('bank_name', 100)->nullable();
            $table->text('bank_account_number')->nullable();
            $table->text('bank_account_name')->nullable();

            $table->string('payment_reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('payment_proof_url')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('merchant_id');
            $table->index('status');
            $table->index(['period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};

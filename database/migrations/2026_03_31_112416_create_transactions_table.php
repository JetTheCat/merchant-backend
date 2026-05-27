<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('merchant_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('user_credit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('credit_package_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('merchant_user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 100);
            $table->string('type', 30);
            $table->string('reference_code', 20)->unique();
            $table->string('status', 20)->default('pending');
            $table->decimal('amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->nullable();
            $table->decimal('balance_before', 10, 2);
            $table->decimal('balance_after', 10, 2);
            $table->decimal('platform_fee_rate', 5, 4)->nullable();
            $table->decimal('platform_fee', 10, 2)->nullable();
            $table->string('qr_code', 100)->nullable();
            $table->timestamp('qr_expires_at')->nullable();
            $table->string('fallback_code', 10)->nullable();
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('merchant_id');
            $table->index('category');
            $table->index('type');
            $table->index('status');
            $table->index('reference_code');
            $table->index('qr_code');
            $table->index('fallback_code');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

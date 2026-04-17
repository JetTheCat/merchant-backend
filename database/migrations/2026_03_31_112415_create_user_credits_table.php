<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_credits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('merchant_id')->constrained()->onDelete('cascade');
            $table->decimal('balance', 10, 2)->default(0);
            $table->decimal('total_purchased', 10, 2)->default(0);
            $table->decimal('total_redeemed', 10, 2)->default(0);
            $table->timestamp('last_purchase_at')->nullable();
            $table->timestamp('last_redemption_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'merchant_id']);
            $table->index('user_id');
            $table->index('merchant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_credits');
    }
};

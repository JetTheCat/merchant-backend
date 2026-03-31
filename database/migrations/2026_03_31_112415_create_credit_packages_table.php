<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_packages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('merchant_id')->constrained()->onDelete('cascade');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->decimal('credit_amount', 10, 2);
            $table->decimal('selling_price', 10, 2);
            $table->decimal('discount_percentage', 5, 2);
            $table->integer('validity_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('purchase_limit_per_user')->nullable();
            $table->integer('total_quantity')->nullable();
            $table->integer('sold_quantity')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('merchant_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_packages');
    }
};

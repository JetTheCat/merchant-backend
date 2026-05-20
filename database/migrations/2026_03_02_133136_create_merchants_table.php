<?php

declare(strict_types=1);

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
        Schema::create('merchants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->string('code', 20)->nullable()->unique();
            $table->string('business_name', 255);
            $table->text('business_registration_number')->nullable();
            $table->foreignUuid('category_id')->constrained()->onDelete('restrict');
            $table->text('description')->nullable();
            $table->string('logo_url', 500)->nullable();
            $table->string('cover_image_url', 500)->nullable();

            $table->string('phone', 20);
            $table->string('website', 255)->nullable();

            $table->text('address');
            $table->string('city', 100);
            $table->string('state', 100);
            $table->string('postcode', 10);
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            $table->decimal('platform_fee_rate', 5, 4)->default(0.05);
            $table->string('settlement_cycle', 20)->default('weekly');

            $table->string('status', 20)->default('active');

            $table->string('bank_name', 100)->nullable();
            $table->text('bank_account_number')->nullable();
            $table->text('bank_account_name')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('category_id');
            $table->index('status');
            $table->index('city');
            $table->fullText(['name', 'business_name', 'description']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};

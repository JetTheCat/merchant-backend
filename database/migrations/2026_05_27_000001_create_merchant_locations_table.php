<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_locations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('merchant_id')->constrained()->onDelete('cascade');
            $table->string('name', 100);
            $table->uuid('redemption_code')->unique();
            $table->string('area', 100);
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index('merchant_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_locations');
    }
};

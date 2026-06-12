<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_users', function (Blueprint $table) {
            $table->foreignUuid('merchant_location_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->index('merchant_location_id');
        });
    }

    public function down(): void
    {
        Schema::table('merchant_users', function (Blueprint $table) {
            $table->dropForeign(['merchant_location_id']);
            $table->dropIndex(['merchant_location_id']);
            $table->dropColumn('merchant_location_id');
        });
    }
};

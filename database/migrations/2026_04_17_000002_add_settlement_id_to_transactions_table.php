<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignUuid('settlement_id')->nullable()->constrained()->nullOnDelete()->after('merchant_user_id');
            $table->index('settlement_id');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['settlement_id']);
            $table->dropIndex(['settlement_id']);
            $table->dropColumn('settlement_id');
        });
    }
};

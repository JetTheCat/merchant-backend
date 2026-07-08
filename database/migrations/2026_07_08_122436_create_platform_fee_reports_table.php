<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_fee_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('file_path');
            $table->decimal('total_gmv', 12, 2);
            $table->decimal('total_platform_fees', 12, 2);
            $table->decimal('total_processing_costs', 12, 2);
            $table->decimal('net_earnings', 12, 2);
            $table->string('status');
            $table->string('payment_reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->uuid('generated_by');
            $table->timestamps();

            $table->foreign('generated_by')->references('id')->on('users');

            $table->softDeletes();

            $table->index(['period_start', 'period_end']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_fee_reports');
    }
};

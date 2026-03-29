<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add geography column for spatial queries
        DB::statement('ALTER TABLE merchants ADD COLUMN location GEOGRAPHY(Point, 4326)');

        // Create spatial index for fast proximity queries
        DB::statement('CREATE INDEX merchants_location_idx ON merchants USING GIST(location)');

        // Populate location column from existing lat/lng data
        DB::statement('
            UPDATE merchants 
            SET location = ST_SetSRID(ST_MakePoint(longitude, latitude), 4326)
            WHERE latitude IS NOT NULL AND longitude IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchants', function ($table) {
            $table->dropColumn('location');
        });
    }
};

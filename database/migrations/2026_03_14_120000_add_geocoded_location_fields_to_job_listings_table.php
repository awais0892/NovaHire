<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            $table->string('location_label')->nullable()->after('location');
            $table->string('location_place_id')->nullable()->after('location_label');
            $table->decimal('location_latitude', 10, 7)->nullable()->after('location_place_id');
            $table->decimal('location_longitude', 10, 7)->nullable()->after('location_latitude');
            $table->string('location_city')->nullable()->after('location_longitude');
            $table->string('location_region')->nullable()->after('location_city');
            $table->string('location_country_code', 2)->nullable()->after('location_region');

            $table->index(
                ['status', 'location_latitude', 'location_longitude'],
                'job_listings_status_lat_lng_index'
            );
            $table->index('location_place_id', 'job_listings_location_place_id_index');
        });

        DB::table('job_listings')
            ->whereNull('location_label')
            ->update([
                'location_label' => DB::raw('location'),
            ]);
    }

    public function down(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            $table->dropIndex('job_listings_status_lat_lng_index');
            $table->dropIndex('job_listings_location_place_id_index');
            $table->dropColumn([
                'location_label',
                'location_place_id',
                'location_latitude',
                'location_longitude',
                'location_city',
                'location_region',
                'location_country_code',
            ]);
        });
    }
};

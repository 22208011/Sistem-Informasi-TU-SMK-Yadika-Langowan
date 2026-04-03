<?php

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
        Schema::table('school_profiles', function (Blueprint $table) {
            // Location coordinates for maps
            $table->decimal('latitude', 10, 8)->nullable()->after('postal_code');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->string('maps_url')->nullable()->after('longitude');

            // Additional contact info
            $table->string('whatsapp_1', 20)->nullable()->after('phone');
            $table->string('whatsapp_1_name', 100)->nullable()->after('whatsapp_1');
            $table->string('whatsapp_2', 20)->nullable()->after('whatsapp_1_name');
            $table->string('whatsapp_2_name', 100)->nullable()->after('whatsapp_2');

            // Social media
            $table->string('facebook')->nullable()->after('website');
            $table->string('instagram')->nullable()->after('facebook');
            $table->string('youtube')->nullable()->after('instagram');
            $table->string('tiktok')->nullable()->after('youtube');

            // Operational hours
            $table->string('operational_days')->nullable()->after('tiktok');
            $table->time('operational_start')->nullable()->after('operational_days');
            $table->time('operational_end')->nullable()->after('operational_start');
            $table->string('timezone', 10)->default('WITA')->after('operational_end');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'latitude', 'longitude', 'maps_url',
                'whatsapp_1', 'whatsapp_1_name', 'whatsapp_2', 'whatsapp_2_name',
                'facebook', 'instagram', 'youtube', 'tiktok',
                'operational_days', 'operational_start', 'operational_end', 'timezone',
            ]);
        });
    }
};

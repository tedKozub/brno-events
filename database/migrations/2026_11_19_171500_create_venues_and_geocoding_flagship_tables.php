<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('venues')->nullOnDelete();
            $table->unsignedBigInteger('osm_place_id')->nullable()->after('longitude')->index();
            $table->string('place_type')->nullable()->default('other')->after('osm_place_id');
            $table->boolean('is_verified')->default(true)->after('place_type');
        });

        Schema::create('venue_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained('venues')->cascadeOnDelete();
            $table->string('alias');
            $table->string('alias_normalized')->index();
            $table->timestamps();
        });

        Schema::create('geocoding_cache', function (Blueprint $table) {
            $table->string('query_hash', 64)->primary();
            $table->text('raw_query');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedBigInteger('osm_place_id')->nullable()->index();
            $table->string('osm_type', 32)->nullable();
            $table->text('formatted_address')->nullable();
            $table->longText('raw_response')->nullable();
            $table->timestamps();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->string('room_detail')->nullable()->after('location_name');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('room_detail');
        });

        Schema::dropIfExists('geocoding_cache');
        Schema::dropIfExists('venue_aliases');

        Schema::table('venues', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'osm_place_id', 'place_type', 'is_verified']);
        });
    }
};

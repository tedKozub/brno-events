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
        Schema::table('analytics_events', function (Blueprint $table) {
            $table->string('country_code', 8)->default('CZ')->index()->after('referrer_host');
            $table->string('traffic_type', 16)->default('real')->index()->after('country_code'); // real, internal, test
            $table->boolean('is_internal')->default(false)->index()->after('traffic_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analytics_events', function (Blueprint $table) {
            $table->dropColumn(['country_code', 'traffic_type', 'is_internal']);
        });
    }
};

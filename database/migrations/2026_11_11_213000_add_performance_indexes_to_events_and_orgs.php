<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->index('category_id');
            $table->index('organization_id');
            $table->index('venue_id');
            $table->index('price_type');
            $table->index(['status', 'starts_at']);
            $table->index(['status', 'category_id', 'starts_at']);
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->index('university');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropIndex(['university']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['category_id']);
            $table->dropIndex(['organization_id']);
            $table->dropIndex(['venue_id']);
            $table->dropIndex(['price_type']);
            $table->dropIndex(['status', 'starts_at']);
            $table->dropIndex(['status', 'category_id', 'starts_at']);
        });
    }
};

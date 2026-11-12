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
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 32)->index(); // pageview, outbound_click, ical_download, ticket_click, share_click, search
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->string('path')->nullable();
            $table->string('category_slug', 64)->nullable()->index();
            $table->string('university', 32)->nullable()->index();
            $table->string('search_query', 128)->nullable();
            $table->string('device_type', 16)->default('desktop');
            $table->string('visitor_hash', 16)->nullable()->index(); // Anonymized daily hash (salt rotates daily)
            $table->string('referrer_host', 128)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['event_type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};

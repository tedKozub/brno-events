<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained('venues')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at')->nullable()->index();
            $table->boolean('is_all_day')->default(false);
            $table->string('location_name')->nullable();
            $table->string('price_type')->default('free'); // free, paid, voluntary
            $table->decimal('price_amount', 8, 2)->nullable();
            $table->string('price_currency')->default('CZK');
            $table->string('ticket_url')->nullable();
            $table->string('facebook_event_url')->nullable();
            $table->string('instagram_post_url')->nullable();
            $table->string('source_type')->default('manual'); // manual, scraper_web, scraper_facebook, scraper_instagram
            $table->string('source_url')->nullable();
            $table->string('status')->default('published')->index(); // published, pending_review, draft, cancelled
            $table->boolean('is_featured')->default(false)->index();
            $table->unsignedBigInteger('views_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};

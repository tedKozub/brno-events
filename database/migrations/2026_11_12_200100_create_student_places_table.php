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
        Schema::create('student_places', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_place_category_id')->nullable()->constrained('student_place_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('address');
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->text('description');
            $table->string('highlight')->nullable();
            $table->json('tags')->nullable();
            $table->string('website_url')->nullable();
            $table->string('instagram_handle')->nullable();
            $table->string('cover_image')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
            $table->index('student_place_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_places');
    }
};

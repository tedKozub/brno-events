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
        Schema::create('student_place_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_place_category_id')->nullable()->constrained('student_place_categories')->nullOnDelete();
            $table->string('name');
            $table->string('address');
            $table->string('highlight')->nullable();
            $table->text('description');
            $table->json('tags')->nullable();
            $table->string('website_url')->nullable();
            $table->string('instagram_handle')->nullable();
            $table->string('submitter_name')->nullable();
            $table->string('submitter_email')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->foreignId('approved_student_place_id')->nullable()->constrained('student_places')->nullOnDelete();
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_place_suggestions');
    }
};

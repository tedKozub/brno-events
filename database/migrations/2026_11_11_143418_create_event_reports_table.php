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
        Schema::create('event_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('reason'); // e.g. cancelled, wrong_time, wrong_location, wrong_organizer, other
            $table->text('details')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('status')->default('pending'); // pending, resolved, dismissed
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_reports');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_places', function (Blueprint $table) {
            $table->text('embedding')->nullable()->after('is_active');
            $table->index(['sort_order', 'id'], 'student_places_sort_order_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('student_places', function (Blueprint $table) {
            $table->dropColumn('embedding');
            $table->dropIndex('student_places_sort_order_id_index');
        });
    }
};

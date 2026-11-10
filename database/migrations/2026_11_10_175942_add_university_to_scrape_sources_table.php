<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scrape_sources', function (Blueprint $table) {
            $table->string('university')->default('OTHER')->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('scrape_sources', function (Blueprint $table) {
            $table->dropColumn('university');
        });
    }
};

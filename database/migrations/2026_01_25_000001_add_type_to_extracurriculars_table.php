<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add 'type' field to extracurriculars table to distinguish between
     * Organisasi (OSIS) and Ekstrakurikuler (PMR, Pramuka, etc.)
     */
    public function up(): void
    {
        Schema::table('extracurriculars', function (Blueprint $table) {
            $table->string('type')->default('ekstrakurikuler')->after('name');
            $table->index('type');
            $table->index(['type', 'is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('extracurriculars', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropIndex(['type', 'is_active', 'sort_order']);
            $table->dropColumn('type');
        });
    }
};

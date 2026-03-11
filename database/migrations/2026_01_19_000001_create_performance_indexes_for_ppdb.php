<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Index untuk spmb_settings (sering di-query dengan section_key)
        Schema::table('spmb_settings', function (Blueprint $table) {
            $table->index('section_key', 'idx_spmb_section_key');
        });

        // Composite index untuk academic_calendar_contents (untuk ordered() scope)
        Schema::table('academic_calendar_contents', function (Blueprint $table) {
            $table->index(
                ['is_active', 'academic_year_start', 'semester', 'sort_order'],
                'idx_academic_calendar_ordered'
            );
        });

        // Composite index untuk posts (untuk filter PPDB dan berita)
        Schema::table('posts', function (Blueprint $table) {
            $table->index(['status', 'category', 'published_at'], 'idx_posts_status_category_date');
        });

        // Index untuk programs (untuk featured dan sorting)
        Schema::table('programs', function (Blueprint $table) {
            $table->index(['is_featured', 'sort_order'], 'idx_programs_featured_sort');
        });

        // Index untuk extracurriculars (untuk active dan sorting)
        Schema::table('extracurriculars', function (Blueprint $table) {
            $table->index(['is_active', 'sort_order'], 'idx_extracurriculars_active_sort');
        });

        // Index untuk faqs (untuk published)
        Schema::table('faqs', function (Blueprint $table) {
            $table->index('is_published', 'idx_faqs_published');
        });

        // Index untuk teachers (untuk active dan type)
        Schema::table('teachers', function (Blueprint $table) {
            $table->index(['is_active', 'type'], 'idx_teachers_active_type');
        });

        // Fulltext indexes untuk PostgreSQL (untuk search performance)
        if (DB::connection()->getDriverName() === 'pgsql') {
            // Posts fulltext search
            DB::statement("
                CREATE INDEX IF NOT EXISTS idx_posts_fulltext 
                ON posts USING GIN(to_tsvector('indonesian', coalesce(title, '') || ' ' || coalesce(excerpt, '') || ' ' || coalesce(content, '')))
            ");

            // Programs fulltext search
            DB::statement("
                CREATE INDEX IF NOT EXISTS idx_programs_fulltext 
                ON programs USING GIN(to_tsvector('indonesian', coalesce(title, '') || ' ' || coalesce(description, '')))
            ");

            // Extracurriculars fulltext search
            DB::statement("
                CREATE INDEX IF NOT EXISTS idx_extracurriculars_fulltext 
                ON extracurriculars USING GIN(to_tsvector('indonesian', coalesce(name, '') || ' ' || coalesce(description, '')))
            ");

            // FAQs fulltext search
            DB::statement("
                CREATE INDEX IF NOT EXISTS idx_faqs_fulltext 
                ON faqs USING GIN(to_tsvector('indonesian', coalesce(question, '') || ' ' || coalesce(answer, '')))
            ");

            // Teachers fulltext search
            DB::statement("
                CREATE INDEX IF NOT EXISTS idx_teachers_fulltext 
                ON teachers USING GIN(to_tsvector('indonesian', coalesce(name, '') || ' ' || coalesce(position, '') || ' ' || coalesce(department, '')))
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spmb_settings', function (Blueprint $table) {
            $table->dropIndex('idx_spmb_section_key');
        });

        Schema::table('academic_calendar_contents', function (Blueprint $table) {
            $table->dropIndex('idx_academic_calendar_ordered');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('idx_posts_status_category_date');
        });

        Schema::table('programs', function (Blueprint $table) {
            $table->dropIndex('idx_programs_featured_sort');
        });

        Schema::table('extracurriculars', function (Blueprint $table) {
            $table->dropIndex('idx_extracurriculars_active_sort');
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->dropIndex('idx_faqs_published');
        });

        Schema::table('teachers', function (Blueprint $table) {
            $table->dropIndex('idx_teachers_active_type');
        });

        // Drop fulltext indexes
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("DROP INDEX IF EXISTS idx_posts_fulltext");
            DB::statement("DROP INDEX IF EXISTS idx_programs_fulltext");
            DB::statement("DROP INDEX IF EXISTS idx_extracurriculars_fulltext");
            DB::statement("DROP INDEX IF EXISTS idx_faqs_fulltext");
            DB::statement("DROP INDEX IF EXISTS idx_teachers_fulltext");
        }
    }
};

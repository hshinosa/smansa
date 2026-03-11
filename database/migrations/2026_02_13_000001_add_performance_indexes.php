<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration to add performance indexes for frequently queried columns
 * Part of Immediate Action Plan - Database Optimization
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        // Posts table indexes
        if (!$this->indexExists('posts', 'idx_posts_status_published')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->index(['status', 'published_at'], 'idx_posts_status_published');
            });
        }
        if (!$this->indexExists('posts', 'idx_posts_category')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->index('category', 'idx_posts_category');
            });
        }
        if (!$this->indexExists('posts', 'idx_posts_slug')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->index('slug', 'idx_posts_slug');
            });
        }
        if (!$this->indexExists('posts', 'idx_posts_author')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->index('author_id', 'idx_posts_author');
            });
        }
        if (!$this->indexExists('posts', 'idx_posts_views')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->index('views_count', 'idx_posts_views');
            });
        }

        // Galleries table indexes
        if (!$this->indexExists('galleries', 'idx_galleries_type')) {
            Schema::table('galleries', function (Blueprint $table) {
                $table->index('type', 'idx_galleries_type');
            });
        }
        if (!$this->indexExists('galleries', 'idx_galleries_featured')) {
            Schema::table('galleries', function (Blueprint $table) {
                $table->index('is_featured', 'idx_galleries_featured');
            });
        }
        if (!$this->indexExists('galleries', 'idx_galleries_created')) {
            Schema::table('galleries', function (Blueprint $table) {
                $table->index('created_at', 'idx_galleries_created');
            });
        }

        // Teachers table indexes
        if (!$this->indexExists('teachers', 'idx_teachers_active_sort')) {
            Schema::table('teachers', function (Blueprint $table) {
                $table->index(['is_active', 'sort_order'], 'idx_teachers_active_sort');
            });
        }
        if (!$this->indexExists('teachers', 'idx_teachers_type')) {
            Schema::table('teachers', function (Blueprint $table) {
                $table->index('type', 'idx_teachers_type');
            });
        }

        // Extracurriculars table indexes
        if (!$this->indexExists('extracurriculars', 'idx_extracurriculars_active_sort')) {
            Schema::table('extracurriculars', function (Blueprint $table) {
                $table->index(['is_active', 'sort_order'], 'idx_extracurriculars_active_sort');
            });
        }

        // Alumni table indexes
        if (!$this->indexExists('alumnis', 'idx_alumnis_published_sort')) {
            Schema::table('alumnis', function (Blueprint $table) {
                $table->index(['is_published', 'sort_order'], 'idx_alumnis_published_sort');
            });
        }
        if (!$this->indexExists('alumnis', 'idx_alumnis_year')) {
            Schema::table('alumnis', function (Blueprint $table) {
                $table->index('graduation_year', 'idx_alumnis_year');
            });
        }

        // FAQs table indexes
        if (!$this->indexExists('faqs', 'idx_faqs_published_sort')) {
            Schema::table('faqs', function (Blueprint $table) {
                $table->index(['is_published', 'sort_order'], 'idx_faqs_published_sort');
            });
        }

        // Academic calendar contents table indexes
        if (!$this->indexExists('academic_calendar_contents', 'idx_calendar_active_year')) {
            Schema::table('academic_calendar_contents', function (Blueprint $table) {
                $table->index(['is_active', 'academic_year_start'], 'idx_calendar_active_year');
            });
        }
        if (!$this->indexExists('academic_calendar_contents', 'idx_calendar_semester')) {
            Schema::table('academic_calendar_contents', function (Blueprint $table) {
                $table->index('semester', 'idx_calendar_semester');
            });
        }

        // PTN admissions table indexes
        if (!$this->indexExists('ptn_admissions', 'idx_admissions_university')) {
            Schema::table('ptn_admissions', function (Blueprint $table) {
                $table->index('university_id', 'idx_admissions_university');
            });
        }
        if (!$this->indexExists('ptn_admissions', 'idx_admissions_batch')) {
            Schema::table('ptn_admissions', function (Blueprint $table) {
                $table->index('batch_id', 'idx_admissions_batch');
            });
        }
        if (!$this->indexExists('ptn_admissions', 'idx_admissions_uni_batch')) {
            Schema::table('ptn_admissions', function (Blueprint $table) {
                $table->index(['university_id', 'batch_id'], 'idx_admissions_uni_batch');
            });
        }

        // PTN admission batches table indexes
        if (!$this->indexExists('ptn_admission_batches', 'idx_batches_published_sort')) {
            Schema::table('ptn_admission_batches', function (Blueprint $table) {
                $table->index(['is_published', 'sort_order'], 'idx_batches_published_sort');
            });
        }
        if (!$this->indexExists('ptn_admission_batches', 'idx_batches_year')) {
            Schema::table('ptn_admission_batches', function (Blueprint $table) {
                $table->index('year', 'idx_batches_year');
            });
        }
        if (!$this->indexExists('ptn_admission_batches', 'idx_batches_type')) {
            Schema::table('ptn_admission_batches', function (Blueprint $table) {
                $table->index('type', 'idx_batches_type');
            });
        }

        // PTN universities table indexes
        if (!$this->indexExists('ptn_universities', 'idx_universities_active_sort')) {
            Schema::table('ptn_universities', function (Blueprint $table) {
                $table->index(['is_active', 'sort_order'], 'idx_universities_active_sort');
            });
        }

        // TKA averages table indexes
        if (!$this->indexExists('tka_averages', 'idx_tka_year_type')) {
            Schema::table('tka_averages', function (Blueprint $table) {
                $table->index(['academic_year', 'exam_type'], 'idx_tka_year_type');
            });
        }
        if (!$this->indexExists('tka_averages', 'idx_tka_subject')) {
            Schema::table('tka_averages', function (Blueprint $table) {
                $table->index('subject_name', 'idx_tka_subject');
            });
        }

        // Programs table indexes
        if (!$this->indexExists('programs', 'idx_programs_featured_category')) {
            Schema::table('programs', function (Blueprint $table) {
                $table->index(['is_featured', 'category'], 'idx_programs_featured_category');
            });
        }
        if (!$this->indexExists('programs', 'idx_programs_sort')) {
            Schema::table('programs', function (Blueprint $table) {
                $table->index('sort_order', 'idx_programs_sort');
            });
        }

        // Contact messages table indexes
        if (!$this->indexExists('contact_messages', 'idx_contact_read')) {
            Schema::table('contact_messages', function (Blueprint $table) {
                $table->index('is_read', 'idx_contact_read');
            });
        }
        if (!$this->indexExists('contact_messages', 'idx_contact_created')) {
            Schema::table('contact_messages', function (Blueprint $table) {
                $table->index('created_at', 'idx_contact_created');
            });
        }

        // RAG document chunks table indexes
        if (!$this->indexExists('rag_document_chunks', 'idx_chunks_document')) {
            Schema::table('rag_document_chunks', function (Blueprint $table) {
                $table->index('document_id', 'idx_chunks_document');
            });
        }

        // RAG documents table indexes
        if (!$this->indexExists('rag_documents', 'idx_rag_docs_active_processed')) {
            Schema::table('rag_documents', function (Blueprint $table) {
                $table->index(['is_active', 'is_processed'], 'idx_rag_docs_active_processed');
            });
        }
        if (!$this->indexExists('rag_documents', 'idx_rag_docs_category')) {
            Schema::table('rag_documents', function (Blueprint $table) {
                $table->index('category', 'idx_rag_docs_category');
            });
        }

        // Chat histories table indexes
        if (!$this->indexExists('chat_histories', 'idx_chat_session')) {
            Schema::table('chat_histories', function (Blueprint $table) {
                $table->index('session_id', 'idx_chat_session');
            });
        }
        if (!$this->indexExists('chat_histories', 'idx_chat_created')) {
            Schema::table('chat_histories', function (Blueprint $table) {
                $table->index('created_at', 'idx_chat_created');
            });
        }

        // Activity logs table indexes
        // Consolidated schema uses `causer_id` + `description`, while legacy schema may use
        // `admin_id` + `action`. Create only indexes that match existing columns.
        if (Schema::hasColumn('activity_logs', 'admin_id')) {
            if (!$this->indexExists('activity_logs', 'idx_activity_admin_date')) {
                Schema::table('activity_logs', function (Blueprint $table) {
                    $table->index(['admin_id', 'created_at'], 'idx_activity_admin_date');
                });
            }
        } elseif (Schema::hasColumn('activity_logs', 'causer_id')) {
            if (!$this->indexExists('activity_logs', 'idx_activity_causer_date')) {
                Schema::table('activity_logs', function (Blueprint $table) {
                    $table->index(['causer_id', 'created_at'], 'idx_activity_causer_date');
                });
            }
        }

        if (Schema::hasColumn('activity_logs', 'action')) {
            if (!$this->indexExists('activity_logs', 'idx_activity_action')) {
                Schema::table('activity_logs', function (Blueprint $table) {
                    $table->index('action', 'idx_activity_action');
                });
            }
        } elseif (Schema::hasColumn('activity_logs', 'description')) {
            if (!$this->indexExists('activity_logs', 'idx_activity_description')) {
                Schema::table('activity_logs', function (Blueprint $table) {
                    $table->index('description', 'idx_activity_description');
                });
            }
        }

        // Landing page settings table indexes
        if (!$this->indexExists('landing_page_settings', 'idx_landing_section')) {
            Schema::table('landing_page_settings', function (Blueprint $table) {
                $table->index('section_key', 'idx_landing_section');
            });
        }

        // Site settings table indexes
        if (!$this->indexExists('site_settings', 'idx_site_section')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->index('section_key', 'idx_site_section');
            });
        }

        // School profile settings table indexes
        if (!$this->indexExists('school_profile_settings', 'idx_profile_section')) {
            Schema::table('school_profile_settings', function (Blueprint $table) {
                $table->index('section_key', 'idx_profile_section');
            });
        }

        // SPMB settings table indexes
        if (!$this->indexExists('spmb_settings', 'idx_spmb_section')) {
            Schema::table('spmb_settings', function (Blueprint $table) {
                $table->index('section_key', 'idx_spmb_section');
            });
        }

        // Program studi settings table indexes
        if (!$this->indexExists('program_studi_settings', 'idx_prodi_program_section')) {
            Schema::table('program_studi_settings', function (Blueprint $table) {
                $table->index(['program_name', 'section_key'], 'idx_prodi_program_section');
            });
        }

        // Curriculum settings table indexes
        if (!$this->indexExists('curriculum_settings', 'idx_curriculum_section')) {
            Schema::table('curriculum_settings', function (Blueprint $table) {
                $table->index('section_key', 'idx_curriculum_section');
            });
        }
    }

    /**
     * Check if an index exists on a table
     */
    protected function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name=? AND name=?", [$table, $indexName]);
            return count($indexes) > 0;
        }

        if ($driver === 'pgsql') {
            $indexes = DB::select("SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?", [$table, $indexName]);
            return count($indexes) > 0;
        }

        if ($driver === 'mysql') {
            $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
            return count($indexes) > 0;
        }

        return false;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes if they exist
        $this->dropIndexIfExists('posts', 'idx_posts_status_published');
        $this->dropIndexIfExists('posts', 'idx_posts_category');
        $this->dropIndexIfExists('posts', 'idx_posts_slug');
        $this->dropIndexIfExists('posts', 'idx_posts_author');
        $this->dropIndexIfExists('posts', 'idx_posts_views');

        $this->dropIndexIfExists('galleries', 'idx_galleries_type');
        $this->dropIndexIfExists('galleries', 'idx_galleries_featured');
        $this->dropIndexIfExists('galleries', 'idx_galleries_created');

        $this->dropIndexIfExists('teachers', 'idx_teachers_active_sort');
        $this->dropIndexIfExists('teachers', 'idx_teachers_type');

        $this->dropIndexIfExists('extracurriculars', 'idx_extracurriculars_active_sort');

        $this->dropIndexIfExists('alumnis', 'idx_alumnis_published_sort');
        $this->dropIndexIfExists('alumnis', 'idx_alumnis_year');

        $this->dropIndexIfExists('faqs', 'idx_faqs_published_sort');

        $this->dropIndexIfExists('academic_calendar_contents', 'idx_calendar_active_year');
        $this->dropIndexIfExists('academic_calendar_contents', 'idx_calendar_semester');

        $this->dropIndexIfExists('ptn_admissions', 'idx_admissions_university');
        $this->dropIndexIfExists('ptn_admissions', 'idx_admissions_batch');
        $this->dropIndexIfExists('ptn_admissions', 'idx_admissions_uni_batch');

        $this->dropIndexIfExists('ptn_admission_batches', 'idx_batches_published_sort');
        $this->dropIndexIfExists('ptn_admission_batches', 'idx_batches_year');
        $this->dropIndexIfExists('ptn_admission_batches', 'idx_batches_type');

        $this->dropIndexIfExists('ptn_universities', 'idx_universities_active_sort');

        $this->dropIndexIfExists('tka_averages', 'idx_tka_year_type');
        $this->dropIndexIfExists('tka_averages', 'idx_tka_subject');

        $this->dropIndexIfExists('programs', 'idx_programs_featured_category');
        $this->dropIndexIfExists('programs', 'idx_programs_sort');

        $this->dropIndexIfExists('contact_messages', 'idx_contact_read');
        $this->dropIndexIfExists('contact_messages', 'idx_contact_created');

        $this->dropIndexIfExists('rag_document_chunks', 'idx_chunks_document');

        $this->dropIndexIfExists('rag_documents', 'idx_rag_docs_active_processed');
        $this->dropIndexIfExists('rag_documents', 'idx_rag_docs_category');

        $this->dropIndexIfExists('chat_histories', 'idx_chat_session');
        $this->dropIndexIfExists('chat_histories', 'idx_chat_created');

        $this->dropIndexIfExists('activity_logs', 'idx_activity_admin_date');
        $this->dropIndexIfExists('activity_logs', 'idx_activity_causer_date');
        $this->dropIndexIfExists('activity_logs', 'idx_activity_action');
        $this->dropIndexIfExists('activity_logs', 'idx_activity_description');

        $this->dropIndexIfExists('landing_page_settings', 'idx_landing_section');
        $this->dropIndexIfExists('site_settings', 'idx_site_section');
        $this->dropIndexIfExists('school_profile_settings', 'idx_profile_section');
        $this->dropIndexIfExists('spmb_settings', 'idx_spmb_section');
        $this->dropIndexIfExists('program_studi_settings', 'idx_prodi_program_section');
        $this->dropIndexIfExists('curriculum_settings', 'idx_curriculum_section');
    }

    /**
     * Drop an index if it exists
     */
    protected function dropIndexIfExists(string $table, string $indexName): void
    {
        if ($this->indexExists($table, $indexName)) {
            Schema::table($table, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }
    }
};

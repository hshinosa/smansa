# Database Migrations - Consolidated Schema

## 📋 Overview

This folder contains **consolidated migration files** for fresh installations. All incremental updates have been merged into the final schema for cleaner, more maintainable database structure.

## 🗂️ Migration Files

### Core Laravel Migrations (3 files)
1. **`0001_01_01_000000_create_users_table.php`** 
   - Tables: `users`, `password_reset_tokens`, `sessions`
   - Purpose: Laravel authentication system

2. **`0001_01_01_000001_create_cache_table.php`**
   - Tables: `cache`, `cache_locks`
   - Purpose: Application caching

3. **`0001_01_01_000002_create_jobs_table.php`**
   - Tables: `jobs`, `job_batches`, `failed_jobs`
   - Purpose: Queue system

### Application Migrations (4 consolidated files)

4. **`2025_01_01_000010_create_core_app_tables.php`**
   - Tables: `admins`, `activity_logs`, `site_settings`, `contact_messages`
   - Purpose: Core application functionality
   - Includes: All indexes for performance

5. **`2025_01_01_000020_create_content_tables.php`** ⭐ **CONSOLIDATED**
   - Tables: `landing_page_settings`, `programs`, `galleries`, `school_profile_settings`, `teachers`, `alumnis`, `faqs`
   - Purpose: Content management system
   - Consolidated from: 3 legacy migrations
   - Final Schema includes:
     - `alumnis`: Video support fields (video_url, video_thumbnail_url, video_source, content_type)
     - `alumnis`: Removed fields (current_position, education, category)
     - `alumnis`: testimonial made nullable
     - `galleries`: date column added
     - `programs`: slug field added for SEO
   - Includes: All performance indexes

6. **`2025_01_01_000030_create_academic_and_activity_tables.php`** ⭐ **CONSOLIDATED**
   - Tables: `academic_calendar_contents`, `curriculum_settings`, `program_studi_settings`, `spmb_settings`, `extracurriculars`, `posts`
   - Purpose: Academic content and student activities
   - Consolidated from: 4 legacy migrations
   - Final Schema includes:
     - `program_studi_settings`: thumbnail_card_url field
     - `posts`: All performance indexes for search optimization
   - Includes: All performance indexes

7. **`2025_01_01_000040_create_ai_and_rag_tables.php`**
   - Tables: `ai_settings`, `rag_documents`, `rag_document_chunks`, `chat_histories`
   - Purpose: AI chatbot and RAG system
   - Includes: All indexes for vector search

### Third-Party Library Migrations (1 file)

8. **`2025_12_29_165433_create_media_table.php`**
   - Table: `media`
   - Purpose: Spatie Media Library (image uploads, WebP conversion)

### Performance & Enhancement Migrations (1 file)

9. **`2026_01_12_000001_add_fulltext_search_index.php`**
   - Adds: Full-text search indexes on `posts` table
   - Supports: PostgreSQL (GIN index) and MySQL (FULLTEXT index)

## 🔄 Migration Legacy Folder

The `migrations_legacy/` folder contains all original migration files including:
- Original table creation migrations
- Incremental update migrations (video support, field additions, etc.)
- Field removal migrations
- Index creation migrations

**Purpose:** Backup and reference for existing production databases

## ✅ Benefits of Consolidated Migrations

1. **Cleaner Codebase**: Reduced from 20 files to 9 files
2. **Easier to Read**: Final schema in one place, no need to trace through multiple files
3. **Faster Fresh Installations**: Single migration run creates complete schema
4. **Better Documentation**: Clear table structure and relationships
5. **Includes All Enhancements**: Video support, indexes, full-text search built-in

## 🚀 Usage

### For Fresh Installation
```bash
php artisan migrate
```

All tables will be created with the final schema including all performance optimizations.

### For Existing Production Database
**⚠️ DO NOT USE THESE MIGRATIONS**

Use the `migrations_legacy/` folder instead as it contains the incremental migration path required for existing databases.

## 📊 Complete Database Schema

### Tables by Domain

#### Core Application (4 tables)
- `admins` - Admin users with guard authentication
- `activity_logs` - System audit trail
- `site_settings` - Global site configuration
- `contact_messages` - Contact form submissions

#### Content Management (7 tables)
- `landing_page_settings` - Landing page sections
- `programs` - School programs
- `galleries` - Photo/video gallery
- `school_profile_settings` - School profile sections
- `teachers` - Teachers and staff
- `alumnis` - Alumni testimonials (with video support)
- `faqs` - Frequently asked questions

#### Academic & Activities (6 tables)
- `academic_calendar_contents` - Academic calendar
- `curriculum_settings` - Curriculum configuration
- `program_studi_settings` - Program study details (MIPA/IPS/Bahasa)
- `spmb_settings` - Admission/registration settings
- `extracurriculars` - Extracurricular activities
- `posts` - News, announcements, achievements (with full-text search)

#### AI & RAG System (4 tables)
- `ai_settings` - AI configuration
- `rag_documents` - Knowledge base documents
- `rag_document_chunks` - Vector embeddings for RAG
- `chat_histories` - Chatbot conversation history

#### Media & Cache (7 tables)
- `media` - Spatie Media Library
- `users` - User accounts
- `sessions` - User sessions
- `cache` / `cache_locks` - Application cache
- `jobs` / `job_batches` / `failed_jobs` - Queue system

## 🔍 Key Features Included

✅ All performance indexes  
✅ Full-text search (PostgreSQL & MySQL)  
✅ Video support for alumni  
✅ WebP image conversion support  
✅ RAG system for AI chatbot  
✅ Polymorphic media relationships  
✅ Soft deletes on RAG documents  
✅ Foreign key constraints  
✅ Optimized query indexes  

## 📝 Notes

- All JSON fields use proper casting in models
- Indexes are strategically placed for common queries
- Timestamps are on all tables
- Nullable fields are clearly marked
- Enum values are documented in comments
- Foreign keys have cascade delete where appropriate

---

**Last Updated:** January 17, 2026  
**Migration Version:** 2.0 (Consolidated)  
**Total Tables:** 28 tables

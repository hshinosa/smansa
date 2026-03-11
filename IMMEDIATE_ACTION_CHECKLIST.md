# Immediate Action Checklist (0-3 Bulan)

**Proyek:** SMAN 1 Baleendah Web Application  
**Periode:** Q1 2026  
**Status:** In Progress  
**Last Updated:** 2026-02-13

---

## 📋 Daftar Tugas

### A. Refactor Routes ke Dedicated Controllers

#### A.1 Public Page Controllers
- [x] **Buat `LandingPageController`** - Halaman utama dengan caching
- [x] **Buat `BeritaController`** - Daftar dan detail berita
- [x] **Buat `ProgramStudiController`** - Halaman MIPA, IPS, Bahasa
- [x] **Buat `AkademikController`** - Ekstrakurikuler, kurikulum, kalender
- [x] **Buat `ProfilController`** - Profil sekolah, visi misi, struktur
- [x] **Buat `PrestasiController`** - Serapan PTN, hasil TKA
- [x] **Buat `AlumniController`** - Halaman alumni
- [x] **Buat `GaleriController`** - Halaman galeri
- [x] **Buat `GuruStaffController`** - Halaman guru dan staff
- [x] **Buat `SpmbController`** - Informasi PPDB
- [x] **Buat `KontakController`** - Halaman kontak

#### A.2 Route Refactoring Tasks
- [x] Pindahkan logic landing page ke `LandingPageController`
- [x] Pindahkan logic berita ke `BeritaController`
- [x] Pindahkan logic program studi ke `ProgramStudiController`
- [x] Pindahkan logic akademik ke `AkademikController`
- [x] Pindahkan logic profil ke `ProfilController`
- [x] Pindahkan logic prestasi ke `PrestasiController`
- [x] Pindahkan logic alumni ke `AlumniController`
- [x] Pindahkan logic galeri ke `GaleriController`
- [x] Pindahkan logic guru/staff ke `GuruStaffController`
- [x] Pindahkan logic SPMB ke `SpmbController`
- [x] Update route definitions di `web.php`
- [x] Test semua halaman publik setelah refactoring

---

### B. Tambah Test Coverage (Target: 80%)

#### B.1 Missing Feature Tests
- [x] **`PublicPagesTest.php`** - Test semua halaman publik (sudah ada)
  - [x] Test landing page loads
  - [x] Test berita page loads
  - [x] Test berita detail loads
  - [x] Test program studi pages load (MIPA, IPS, Bahasa)
  - [x] Test ekstrakurikuler page loads
  - [x] Test kurikulum page loads
  - [x] Test kalender akademik page loads
  - [x] Test profil sekolah page loads
  - [x] Test visi misi page loads
  - [x] Test struktur organisasi page loads
  - [x] Test serapan PTN page loads
  - [x] Test hasil TKA page loads
  - [x] Test alumni page loads
  - [x] Test galeri page loads
  - [x] Test guru staff page loads
  - [x] Test SPMB page loads
  - [x] Test kontak page loads

#### B.2 Missing Unit Tests
- [x] **`CacheServiceTest.php`** - Test cache operations
  - [x] Test getSiteSettings
  - [x] Test getPosts
  - [x] Test getPopularPosts
  - [x] Test invalidate methods
  - [x] Test getStats

- [ ] **`ContentCreationServiceTest.php`** - Test content generation
  - [ ] Test generateNewsArticle
  - [ ] Test formatCaptionAsHtml
  - [ ] Test capitalizeSentences
  - [ ] Test improveContent

- [x] **`EmbeddingServiceTest.php`** - Test embedding service
  - [x] Test createEmbedding success
  - [x] Test createEmbedding with empty text
  - [x] Test normalizeDimensions
  - [x] Test HTTP timeout handling

- [ ] **`GroqServiceTest.php`** - Test Groq API integration
  - [ ] Test chatCompletion
  - [ ] Test contentCompletion
  - [ ] Test analyzeImage
  - [ ] Test getHardcodedFallback
  - [ ] Test API key rotation

- [ ] **`ImageServiceTest.php`** - Test image handling
  - [ ] Test getFirstMediaData
  - [ ] Test getAllMediaData

- [x] **`CustomExceptionsTest.php`** - Test custom exceptions
  - [x] Test ContentNotFoundException
  - [x] Test AiServiceException
  - [x] Test ScraperException

#### B.3 Integration Tests
- [ ] **`ChatFlowTest.php`** - Test complete chat flow
  - [ ] Test send message
  - [ ] Test get history
  - [ ] Test RAG integration

- [ ] **`InstagramFlowTest.php`** - Test Instagram workflow
  - [ ] Test scrape process
  - [ ] Test AI content generation
  - [ ] Test approve/reject workflow

#### B.4 Test Coverage Verification
- [ ] Run `php artisan test --coverage` dan verify 80%+
- [ ] Document areas yang tidak ter-cover
- [ ] Add tests untuk uncovered areas

---

### C. Implement Query Caching

#### C.1 Cache Service Enhancement
- [x] **Review `CacheService.php`** - Pastikan semua method optimal
- [x] **Add cache tags support** - Untuk invalidasi granular
- [ ] **Add cache warming command** - Warm up on deployment (sudah ada `CacheWarmUp`)

#### C.2 Apply Caching to Frequently Accessed Data
- [x] **Landing page data** - Cache hero, about, programs sections
- [x] **Site settings** - Cache all settings dengan invalidation
- [ ] **Navigation data** - Cache menu items
- [x] **FAQ data** - Cache published FAQs
- [x] **Teacher data** - Cache active teachers
- [x] **Extracurricular data** - Cache active extracurriculars

#### C.3 Cache Invalidation
- [x] **Add model observers** - Auto-invalidate on model changes (`CacheInvalidationObserver`)
- [x] **Implement cache tags** - Group related caches
- [ ] **Add manual clear commands** - Artisan commands untuk clear cache

#### C.4 Cache Monitoring
- [ ] **Add cache hit/miss logging** - Monitor cache effectiveness
- [ ] **Add cache stats endpoint** - Untuk monitoring dashboard

---

### D. Add Database Indexes

#### D.1 Posts Table
- [x] Add index pada `status` + `published_at`
- [x] Add index pada `category`
- [x] Add index pada `slug` (unique)
- [x] Add index pada `author_id`

#### D.2 Other Tables
- [x] **galleries** - Add index pada `type`, `is_featured`
- [x] **teachers** - Add index pada `is_active`, `sort_order`
- [x] **extracurriculars** - Add index pada `is_active`, `sort_order`
- [x] **alumnis** - Add index pada `is_published`, `sort_order`
- [x] **faqs** - Add index pada `is_published`, `sort_order`
- [x] **academic_calendar_contents** - Add index pada `is_active`, `academic_year_start`
- [x] **ptn_admissions** - Add index pada `university_id`, `batch_id`
- [x] **tka_averages** - Add index pada `academic_year`, `exam_type`

#### D.3 Migration File
- [x] Buat migration file untuk semua indexes
- [ ] Test migration di local
- [ ] Run migration di staging
- [ ] Run migration di production

---

### E. Standardize Error Handling

#### E.1 Custom Exception Classes
- [x] **Buat `app/Exceptions/` directory**
- [x] **`ContentNotFoundException.php`** - Untuk konten yang tidak ditemukan
- [x] **`AiServiceException.php`** - Untuk error AI services
- [x] **`ScraperException.php`** - Untuk error Instagram scraper
- [ ] **`ValidationException.php`** - Custom validation exception

#### E.2 Exception Handler
- [ ] **Update `app/Exceptions/Handler.php`**
- [ ] Add custom render methods untuk setiap exception type
- [x] Implement consistent JSON response format (di exception classes)
- [ ] Add proper logging context

#### E.3 Error Response Format
- [x] Define standard error response structure:
  ```json
  {
    "success": false,
    "error": {
      "code": "ERROR_CODE",
      "message": "User-friendly message",
      "details": {}
    }
  }
  ```

#### E.4 User-Facing Error Messages
- [x] Review semua error messages (di exception classes)
- [x] Translate ke Bahasa Indonesia
- [ ] Make messages actionable
- [ ] Add support/contact info untuk critical errors

---

### F. Code Quality Improvements

#### F.1 Remove Hardcoded Values
- [x] **Create `config/ai.php`** - Centralized AI configuration
- [x] Move prompts ke config files
- [x] Move default models ke config
- [ ] **`RagService.php`** - Update to use config values
- [ ] **`GroqService.php`** - Update to use config values
- [ ] **`ContentCreationService.php`** - Update to use config values
- [ ] **`web.php`** - Move default content values ke config

#### F.2 Add Missing Type Hints
- [ ] Review semua public methods di Services
- [ ] Add return type hints
- [ ] Add parameter type hints
- [ ] Add PHPDoc comments

#### F.3 Clean Up Unused Code
- [ ] Remove commented code
- [ ] Remove unused imports
- [ ] Remove dead code paths

---

### G. Documentation

#### G.1 API Documentation
- [ ] Document `/api/chat/send` endpoint
- [ ] Document `/api/chat/history` endpoint
- [ ] Document `/api/security/csp-report` endpoint
- [ ] Document `/health` endpoint

#### G.2 Code Documentation
- [ ] Add PHPDoc ke semua Services
- [ ] Add PHPDoc ke semua Models
- [ ] Add inline comments untuk complex logic
- [x] Update AGENTS.md dengan changes

---

## 📊 Progress Tracking

| Category | Total Tasks | Completed | Progress |
|----------|-------------|-----------|----------|
| A. Route Refactoring | 22 | 22 | 100% |
| B. Test Coverage | 35 | 25 | 71% |
| C. Query Caching | 15 | 8 | 53% |
| D. Database Indexes | 15 | 10 | 67% |
| E. Error Handling | 15 | 7 | 47% |
| F. Code Quality | 12 | 2 | 17% |
| G. Documentation | 8 | 1 | 13% |
| **TOTAL** | **122** | **75** | **61%** |

### ✅ Completed Items

#### A. Route Refactoring (22/22) ✅ COMPLETE
- [x] Semua public controllers sudah dibuat
- [x] Semua logic sudah dipindahkan ke controllers
- [x] Routes sudah diupdate
- [x] Testing menyeluruh sudah dilakukan (20/20 feature tests pass)

#### B. Test Coverage (25/35)
- [x] PublicPagesTest sudah ada dan lengkap
- [x] CacheServiceTest sudah dibuat (14 tests)
- [x] EmbeddingServiceTest sudah dibuat (14 tests)
- [x] CustomExceptionsTest sudah dibuat (26 tests)
- [x] RagServiceTest sudah dibuat (13 tests)
- [x] All unit tests passing (85 tests, 206 assertions)

#### C. Query Caching (8/15)
- [x] CacheInvalidationObserver sudah dibuat
- [x] Observer sudah diregistrasi di AppServiceProvider
- [x] Cache invalidation untuk semua model utama

#### D. Database Indexes (10/15)
- [x] Migration file sudah dibuat dengan 40+ indexes
- [x] Migration tested di local (SQLite compatible)

#### E. Error Handling (7/15)
- [x] ContentNotFoundException
- [x] AiServiceException dengan factory methods
- [x] ScraperException dengan factory methods

#### F. Code Quality (2/12)
- [x] config/ai.php sudah dibuat

---

## 🎯 Definition of Done

Setiap task dianggap selesai jika:
- [ ] Code sudah ditulis dan tested
- [ ] Code review sudah dilakukan
- [ ] Documentation sudah diupdate
- [ ] Tidak ada regression pada existing tests
- [ ] Deployed ke staging environment

---

## 📅 Timeline

| Week | Focus Area |
|------|------------|
| Week 1-2 | A. Route Refactoring ✅ |
| Week 3-4 | B. Test Coverage (In Progress) |
| Week 5-6 | C. Query Caching + D. Database Indexes |
| Week 7-8 | E. Error Handling + F. Code Quality |
| Week 9-10 | G. Documentation + Final Review |
| Week 11-12 | Buffer & Deployment |

---

## 🚀 Getting Started

Untuk memulai, pilih salah satu task dari checklist di atas dan:

1. Buat branch baru: `git checkout -b improvement/[task-name]`
2. Kerjakan task tersebut
3. Tulis/update tests
4. Submit untuk code review
5. Merge setelah approved

---

## 📁 Files Created/Modified

### New Files Created:
- `app/Http/Controllers/Public/LandingPageController.php`
- `app/Http/Controllers/Public/BeritaController.php`
- `app/Http/Controllers/Public/ProgramStudiController.php`
- `app/Http/Controllers/Public/AkademikController.php`
- `app/Http/Controllers/Public/ProfilController.php`
- `app/Http/Controllers/Public/PrestasiController.php`
- `app/Http/Controllers/Public/AlumniController.php`
- `app/Http/Controllers/Public/GaleriController.php`
- `app/Http/Controllers/Public/GuruStaffController.php`
- `app/Http/Controllers/Public/SpmbController.php`
- `app/Http/Controllers/Public/KontakController.php`
- `app/Exceptions/ContentNotFoundException.php`
- `app/Exceptions/AiServiceException.php`
- `app/Exceptions/ScraperException.php`
- `app/Observers/CacheInvalidationObserver.php`
- `config/ai.php`
- `database/migrations/2026_02_13_000001_add_performance_indexes.php`
- `tests/Unit/Services/CacheServiceTest.php`
- `tests/Unit/Services/EmbeddingServiceTest.php`
- `tests/Unit/Exceptions/CustomExceptionsTest.php`

### Modified Files:
- `routes/web.php` - Refactored to use controllers
- `app/Providers/AppServiceProvider.php` - Added cache observers

---

*Dokumen ini dibuat pada 2026-02-13 dan diupdate pada 2026-02-13.*

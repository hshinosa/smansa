<?php

namespace App\Services;

use App\Models\RagDocument;
use App\Models\RagDocumentChunk;
use App\Models\AiSetting;
use App\Models\Post;
use App\Models\Teacher;
use App\Models\Extracurricular;
use App\Models\Faq;
use App\Models\Program;
use App\Models\SiteSetting;
use App\Models\AcademicCalendarContent;
use App\Models\Alumni;
use App\Models\Gallery;
use App\Models\LandingPageSetting;
use App\Models\CurriculumSetting;
use App\Models\SchoolProfileSetting;
use App\Models\SpmbSetting;
use App\Models\ProgramStudiSetting;
use App\Models\PtnAdmission;
use App\Models\TkaAverage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RagService
{
    protected GroqService $groq;
    protected EmbeddingService $embeddingService;
    protected int $chunkSize = 512; // tokens per chunk
    protected int $chunkOverlap = 50; // overlap tokens
    protected bool $pgvectorAvailable;

    public function __construct(
        GroqService $groq,
        EmbeddingService $embeddingService
    ) {
        $this->groq = $groq;
        $this->embeddingService = $embeddingService;
        $this->pgvectorAvailable = $this->checkPgvectorAvailability();
    }

    /**
     * Check if pgvector extension is available
     */
    protected function checkPgvectorAvailability(): bool
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return false;
        }

        try {
            $result = DB::select("SELECT COUNT(*) as count FROM pg_extension WHERE extname = 'vector'");
            return ($result[0]->count ?? 0) > 0;
        } catch (\Exception $e) {
            Log::warning('Could not check pgvector availability', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Retrieve relevant documents for a query (from RAG documents using pgvector)
     */
    public function retrieveRelevantChunks(string $query, ?int $topK = null): array
    {
        $topK = $topK ?? AiSetting::get('rag_top_k', 5);

        try {
            // Check if embedding service is available
            if (!$this->embeddingService->isAvailable()) {
                Log::info('Embedding service unavailable, skipping vector search');
                return [];
            }

            // 1. Generate embedding for query
            $queryEmbeddingResult = $this->embeddingService->createEmbedding($query, 'query');

            if (!$queryEmbeddingResult['success']) {
                Log::error('Query embedding failed', ['query' => $query]);
                return [];
            }

            $queryEmbedding = $queryEmbeddingResult['embedding'];

            // 2. Search based on pgvector availability
            if ($this->pgvectorAvailable) {
                return $this->searchWithPgvector($queryEmbedding, $topK);
            } else {
                return $this->searchWithFallback($queryEmbedding, $topK);
            }
        } catch (\Exception $e) {
            Log::error('Failed to retrieve relevant chunks', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Search using pgvector extension
     */
    protected function searchWithPgvector(array $queryEmbedding, int $topK): array
    {
        $embeddingStr = '[' . implode(',', $queryEmbedding) . ']';

        $results = DB::select(
            "SELECT 
                rdc.id,
                rdc.content,
                rdc.document_id,
                rdc.token_count,
                rd.title as document_title,
                rd.category,
                1 - (rdc.embedding <=> ?::vector) as similarity
             FROM rag_document_chunks rdc
             JOIN rag_documents rd ON rd.id = rdc.document_id
             WHERE rd.is_active = true
               AND (rdc.embedding <=> ?::vector) < 0.5
             ORDER BY rdc.embedding <=> ?::vector
             LIMIT ?",
            [$embeddingStr, $embeddingStr, $embeddingStr, $topK]
        );

        $chunksWithSimilarity = [];
        foreach ($results as $result) {
            if ($result->similarity < 0.5) continue;

            $chunksWithSimilarity[] = [
                'id' => $result->id,
                'content' => $result->content,
                'document_id' => $result->document_id,
                'document_title' => $result->document_title ?? 'Unknown',
                'category' => $result->category ?? 'Umum',
                'source' => 'rag_document',
                'similarity' => $result->similarity,
                'token_count' => $result->token_count ?? $this->estimateTokenCount($result->content),
            ];
        }

        return $chunksWithSimilarity;
    }

    /**
     * Search using fallback method (manual cosine similarity calculation)
     */
    protected function searchWithFallback(array $queryEmbedding, int $topK): array
    {
        // Get all active chunks
        $chunks = DB::table('rag_document_chunks')
            ->join('rag_documents', 'rag_documents.id', '=', 'rag_document_chunks.document_id')
            ->where('rag_documents.is_active', true)
            ->select(
                'rag_document_chunks.id',
                'rag_document_chunks.content',
                'rag_document_chunks.document_id',
                'rag_document_chunks.token_count',
                'rag_document_chunks.embedding',
                'rag_documents.title as document_title',
                'rag_documents.category'
            )
            ->get();

        $results = [];
        foreach ($chunks as $chunk) {
            if (empty($chunk->embedding)) continue;

            $chunkEmbedding = $chunk->embedding;
            if (is_string($chunkEmbedding)) {
                $decoded = json_decode($chunkEmbedding, true);
                if (is_array($decoded)) {
                    $chunkEmbedding = $decoded;
                }
            }
            if (!is_array($chunkEmbedding)) continue;

            $similarity = $this->cosineSimilarity($queryEmbedding, $chunkEmbedding);

            if ($similarity >= 0.5) {
                $results[] = [
                    'id' => $chunk->id,
                    'content' => $chunk->content,
                    'document_id' => $chunk->document_id,
                    'document_title' => $chunk->document_title ?? 'Unknown',
                    'category' => $chunk->category ?? 'Umum',
                    'source' => 'rag_document',
                    'similarity' => $similarity,
                    'token_count' => $chunk->token_count ?? $this->estimateTokenCount($chunk->content),
                ];
            }
        }

        // Sort by similarity descending and limit
        usort($results, fn($a, $b) => $b['similarity'] <=> $a['similarity']);
        return array_slice($results, 0, $topK);
    }

    /**
     * Calculate cosine similarity between two vectors
     */
    protected function cosineSimilarity(array $vec1, array $vec2): float
    {
        if (count($vec1) !== count($vec2)) {
            return 0.0;
        }

        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;

        for ($i = 0; $i < count($vec1); $i++) {
            $dotProduct += $vec1[$i] * $vec2[$i];
            $magnitude1 += $vec1[$i] * $vec1[$i];
            $magnitude2 += $vec2[$i] * $vec2[$i];
        }

        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);

        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0.0;
        }

        return $dotProduct / ($magnitude1 * $magnitude2);
    }

    /**
     * Search database for relevant content
     * Included: public/content data used by website and school information pages.
     * Excluded: credentials/sensitive/internal tables (users, admins, ai_settings,
     * contact_messages, chat_histories, activity_logs, instagram bot credentials, etc).
     */
    protected function searchDatabaseContent(string $query, int $limit = 5): array
    {
        $queryLower = strtolower($query);
        $results = [];

        try {
            // 1. Search Site Settings (Profil, Kontak, Sejarah Sekolah)
            $siteSettings = SiteSetting::getCachedAll();
            
            // Check if query is about school profile/contact/general info
            $schoolKeywords = [
                'sejarah', 'profil', 'alamat', 'kontak', 'telepon', 'email', 'lokasi', 'maps', 
                'dimana', 'hubungi', 'social', 'instagram', 'facebook', 'youtube', 'twitter',
                'nomor', 'phone', 'telp', 'contact', 'tentang sekolah', 'info sekolah',
                'visi', 'misi', 'deskripsi', 'gambaran'
            ];
            $isSchoolInfoQuery = false;
            foreach ($schoolKeywords as $keyword) {
                if (strpos($queryLower, $keyword) !== false) {
                    $isSchoolInfoQuery = true;
                    break;
                }
            }

            if ($isSchoolInfoQuery && isset($siteSettings['general'])) {
                $general = $siteSettings['general'];
                $content = "Nama Sekolah: {$general['site_name']}\n";
                $content .= "Alamat: {$general['address']}\n";
                $content .= "Telepon: {$general['phone']}\n";
                $content .= "Email: {$general['email']}\n";
                
                if (isset($siteSettings['social_media'])) {
                    $social = $siteSettings['social_media'];
                    $content .= "\nMedia Sosial:\n";
                    $content .= "- Instagram: {$social['instagram']}\n";
                    $content .= "- Facebook: {$social['facebook']}\n";
                    $content .= "- YouTube: {$social['youtube']}\n";
                }

                if (isset($siteSettings['footer']['footer_description'])) {
                    $content .= "\nProfil Sekolah:\n{$siteSettings['footer']['footer_description']}";
                }

                $results[] = [
                    'id' => 'site_general',
                    'content' => $content,
                    'title' => 'Informasi Sekolah',
                    'category' => 'Profil Sekolah',
                    'source' => 'database',
                    'table' => 'site_settings',
                    'type' => 'Informasi Umum',
                    'similarity' => 0.9, // High relevance for school info queries
                ];
            }

            // 2. Search Posts (Berita & Pengumuman)
            $posts = Post::where('status', 'published')
                ->where(function ($q) use ($queryLower) {
                    $q->where('title', 'like', "%{$queryLower}%")
                      ->orWhere('content', 'like', "%{$queryLower}%")
                      ->orWhere('excerpt', 'like', "%{$queryLower}%");
                })
                ->limit(2)
                ->get();

            foreach ($posts as $post) {
                $results[] = [
                    'id' => $post->id,
                    'content' => trim($post->excerpt . "\n\n" . substr(strip_tags($post->content), 0, 1000)),
                    'title' => $post->title,
                    'category' => $post->category,
                    'source' => 'database',
                    'table' => 'posts',
                    'type' => 'Berita',
                    'similarity' => $this->calculateKeywordMatch($queryLower, $post->title . ' ' . $post->excerpt),
                ];
            }

            // 3. Search FAQs
            $faqs = Faq::where('is_published', true)
                ->where(function ($q) use ($queryLower) {
                    $q->where('question', 'like', "%{$queryLower}%")
                      ->orWhere('answer', 'like', "%{$queryLower}%");
                })
                ->limit(2)
                ->get();

            foreach ($faqs as $faq) {
                $results[] = [
                    'id' => $faq->id,
                    'content' => $faq->answer,
                    'title' => $faq->question,
                    'category' => 'FAQ',
                    'source' => 'database',
                    'table' => 'faqs',
                    'type' => 'FAQ',
                    'similarity' => $this->calculateKeywordMatch($queryLower, $faq->question . ' ' . $faq->answer),
                ];
            }

            // 4. Search Programs (INCLUDING Program Studi/Peminatan: MIPA, IPS, Bahasa)
            $programs = Program::where(function ($q) use ($queryLower) {
                    $q->where('title', 'like', "%{$queryLower}%")
                      ->orWhere('description', 'like', "%{$queryLower}%")
                      ->orWhere('category', 'like', "%{$queryLower}%");
                })
                ->limit(3)
                ->get();

            foreach ($programs as $program) {
                $results[] = [
                    'id' => $program->id,
                    'content' => trim(($program->description ?? $program->title) . "\n\nKategori: " . $program->category),
                    'title' => $program->title,
                    'category' => $program->category,
                    'source' => 'database',
                    'table' => 'programs',
                    'type' => 'Program Sekolah',
                    'similarity' => $this->calculateKeywordMatch($queryLower, $program->title . ' ' . ($program->description ?? '')),
                ];
            }

            // 5. Search Extracurriculars
            $extras = Extracurricular::where('is_active', true)
                ->where(function ($q) use ($queryLower) {
                    $q->where('name', 'like', "%{$queryLower}%")
                      ->orWhere('description', 'like', "%{$queryLower}%")
                      ->orWhere('schedule', 'like', "%{$queryLower}%");
                })
                ->limit(2)
                ->get();

            foreach ($extras as $extra) {
                $results[] = [
                    'id' => $extra->id,
                    'content' => trim($extra->description . "\n\nJadwal: " . $extra->schedule),
                    'title' => $extra->name,
                    'category' => 'Ekstrakurikuler',
                    'source' => 'database',
                    'table' => 'extracurriculars',
                    'type' => 'Kegiatan',
                    'similarity' => $this->calculateKeywordMatch($queryLower, $extra->name . ' ' . $extra->description),
                ];
            }

            // 6. Search Teachers (Guru & Staff)
            // Check if this is a list query for teachers
            $isTeacherListQuery = $this->isTeacherListQuery($queryLower);
            
            // Jika query tentang program/jurusan (IPA/IPS/Bahasa), jangan tampilkan daftar guru
            $isProgramQuery = str_contains($queryLower, 'mipa') || str_contains($queryLower, 'ipa') || 
                str_contains($queryLower, 'ips') || str_contains($queryLower, 'bahasa') ||
                str_contains($queryLower, 'jurusan') || str_contains($queryLower, 'program') ||
                str_contains($queryLower, 'peminatan');
            
            if ($isTeacherListQuery && !$isProgramQuery) {
                // For list queries, get all active teachers and create a summary chunk
                $isStaffQuery = str_contains($queryLower, 'staff') || 
                               str_contains($queryLower, 'tata usaha') || 
                               str_contains($queryLower, 'manajemen') ||
                               str_contains($queryLower, 'humas') ||
                               str_contains($queryLower, 'kesiswaan') ||
                               str_contains($queryLower, 'kurikulum');
                $teacherType = $isStaffQuery ? 'staff' : 'guru';
                $allTeachers = Teacher::where('is_active', true)
                    ->where('type', $teacherType)
                    ->orderBy('department')
                    ->orderBy('name')
                    ->get();
                
                if ($allTeachers->isNotEmpty()) {
                    // Create a comprehensive teacher list as a single chunk
                    $teacherList = [];
                    $totalCount = $allTeachers->count();
                    
                    foreach ($allTeachers->take(20) as $t) { // Limit to 20 for context size
                        $dept = $t->department ? " [{$t->department}]" : '';
                        $pos = $t->position ? " ({$t->position})" : '';
                        $teacherList[] = "- {$t->name}{$pos}{$dept}";
                    }
                    
                    $remainingCount = max(0, $totalCount - 20);
                    $teacherListText = implode("\n", $teacherList);
                    if ($remainingCount > 0) {
                        $teacherListText .= "\n- ... dan {$remainingCount} {$teacherType} lainnya";
                    }
                    
                    $typeLabel = $teacherType === 'staff' ? 'Staff/Tata Usaha' : 'Guru';
                    $results[] = [
                        'id' => 'teacher_list_summary',
                        'content' => "Daftar {$typeLabel} SMAN 1 Baleendah (Total: {$totalCount}):\n\n{$teacherListText}\n\nUntuk detail lengkap tentang masing-masing {$teacherType}, silakan tanya dengan menyebutkan nama.",
                        'title' => "Daftar {$typeLabel} SMANSA",
                        'category' => $typeLabel,
                        'source' => 'database',
                        'table' => 'teachers',
                        'type' => 'Tenaga Pendidik',
                        'similarity' => 0.95, // High similarity for list queries
                    ];
                }
            } else {
                // For specific teacher queries, search individual teachers
                $teachers = Teacher::where('is_active', true)
                    ->where(function ($q) use ($queryLower) {
                        $q->where('name', 'like', "%{$queryLower}%")
                          ->orWhere('position', 'like', "%{$queryLower}%")
                          ->orWhere('department', 'like', "%{$queryLower}%");
                    })
                    ->limit(3)
                    ->get();

                foreach ($teachers as $teacher) {
                    $results[] = [
                        'id' => $teacher->id,
                        'content' => trim($teacher->position . "\n\nDepartemen: " . ($teacher->department ?? '-')),
                        'title' => $teacher->name,
                        'category' => $teacher->type === 'guru' ? 'Guru' : 'Staff',
                        'source' => 'database',
                        'table' => 'teachers',
                        'type' => 'Tenaga Pendidik',
                        'similarity' => $this->calculateKeywordMatch($queryLower, $teacher->name . ' ' . $teacher->position),
                    ];
                }
            }
            
            // Jika query tentang program (IPA/IPS/Bahasa), cari info program bukan guru
            if ($isProgramQuery) {
                // Map query IPA ke MIPA untuk pencarian
                $searchTerms = [$queryLower];
                if (str_contains($queryLower, 'ipa') && !str_contains($queryLower, 'mipa')) {
                    $searchTerms[] = 'mipa';
                }

                // 1. Cari dari Programs table (landing page programs)
                $programs = Program::where('is_featured', true)
                    ->where(function ($q) use ($searchTerms) {
                        foreach ($searchTerms as $term) {
                            $q->orWhere('title', 'like', "%{$term}%")
                              ->orWhere('description', 'like', "%{$term}%")
                              ->orWhere('category', 'like', "%{$term}%");
                        }
                    })
                    ->limit(3)
                    ->get();

                foreach ($programs as $program) {
                    $results[] = [
                        'id' => 'program_' . $program->id,
                        'content' => trim($program->description ?? ''),
                        'title' => $program->title,
                        'category' => $program->category ?? 'Program',
                        'source' => 'database',
                        'table' => 'programs',
                        'type' => 'Program Sekolah',
                        'similarity' => 0.85,
                    ];
                }

                // 2. Cari dari RAG Documents (vectorized ProgramStudiSettings)
                // This contains detailed info about MIPA/IPS/Bahasa
                $programNames = [];
                if (str_contains($queryLower, 'mipa') || str_contains($queryLower, 'ipa')) {
                    $programNames[] = 'MIPA';
                }
                if (str_contains($queryLower, 'ips')) {
                    $programNames[] = 'IPS';
                }
                if (str_contains($queryLower, 'bahasa')) {
                    $programNames[] = 'BAHASA';
                }

                if (!empty($programNames)) {
                    $ragProgramDocs = \App\Models\RagDocument::where('file_type', 'database')
                        ->where('category', 'Program Studi')
                        ->where('is_active', true)
                        ->where(function ($q) use ($programNames) {
                            foreach ($programNames as $progName) {
                                $q->orWhere('title', 'like', "%{$progName}%")
                                  ->orWhere('content', 'like', "%{$progName}%");
                            }
                        })
                        ->limit(10)
                        ->get();

                    foreach ($ragProgramDocs as $doc) {
                        $results[] = [
                            'id' => 'rag_' . $doc->id,
                            'content' => $doc->content,
                            'title' => $doc->title,
                            'category' => 'Program Studi Detail',
                            'source' => 'rag',
                            'table' => 'rag_documents',
                            'type' => 'Program Studi',
                            'similarity' => 0.95, // Higher priority for RAG documents
                        ];
                    }
                }
            }

            // 7. Search Alumni (published testimonials)
            $alumni = Alumni::where('is_published', true)
                ->where(function ($q) use ($queryLower) {
                    $q->where('name', 'like', "%{$queryLower}%")
                      ->orWhere('testimonial', 'like', "%{$queryLower}%")
                      ->orWhere('graduation_year', 'like', "%{$queryLower}%");
                })
                ->limit(2)
                ->get();

            foreach ($alumni as $item) {
                $results[] = [
                    'id' => $item->id,
                    'content' => trim(($item->testimonial ?? '') . "\n\nAngkatan: " . ($item->graduation_year ?? '-')),
                    'title' => $item->name,
                    'category' => 'Alumni',
                    'source' => 'database',
                    'table' => 'alumni',
                    'type' => 'Alumni',
                    'similarity' => $this->calculateKeywordMatch($queryLower, $item->name . ' ' . ($item->testimonial ?? '')),
                ];
            }

            // 8. Search Gallery
            $galleries = Gallery::where(function ($q) use ($queryLower) {
                    $q->where('title', 'like', "%{$queryLower}%")
                      ->orWhere('description', 'like', "%{$queryLower}%")
                      ->orWhere('category', 'like', "%{$queryLower}%");
                })
                ->limit(2)
                ->get();

            foreach ($galleries as $item) {
                $results[] = [
                    'id' => $item->id,
                    'content' => trim(($item->description ?? '') . "\n\nKategori: " . ($item->category ?? '-')),
                    'title' => $item->title,
                    'category' => 'Galeri',
                    'source' => 'database',
                    'table' => 'galleries',
                    'type' => 'Galeri',
                    'similarity' => $this->calculateKeywordMatch($queryLower, $item->title . ' ' . ($item->description ?? '')),
                ];
            }

            // 9. Search Academic Calendar Content
            $calendarItems = AcademicCalendarContent::where('is_active', true)
                ->where(function ($q) use ($queryLower) {
                    $q->where('title', 'like', "%{$queryLower}%")
                      ->orWhere('academic_year_start', 'like', "%{$queryLower}%");
                })
                ->limit(2)
                ->get();

            foreach ($calendarItems as $item) {
                $results[] = [
                    'id' => $item->id,
                    'content' => trim('Semester: ' . ($item->semester_name ?? '-') . "\nTahun Ajaran: " . ($item->academic_year ?? '-')),
                    'title' => $item->title,
                    'category' => 'Kalender Akademik',
                    'source' => 'database',
                    'table' => 'academic_calendar_contents',
                    'type' => 'Kalender Akademik',
                    'similarity' => $this->calculateKeywordMatch($queryLower, $item->title . ' ' . ($item->academic_year ?? '')),
                ];
            }

            // 10. Search Landing Page Settings content
            $landingSettings = LandingPageSetting::where(function ($q) use ($queryLower) {
                    $q->where('section_key', 'like', "%{$queryLower}%")
                      ->orWhere('content', 'like', "%{$queryLower}%");
                })
                ->limit(3)
                ->get();

            foreach ($landingSettings as $item) {
                $content = is_array($item->content) ? json_encode($item->content) : (string) $item->content;
                $results[] = [
                    'id' => $item->id,
                    'content' => Str::limit((string) $content, 1200),
                    'title' => 'Landing: ' . $item->section_key,
                    'category' => 'Landing Page',
                    'source' => 'database',
                    'table' => 'landing_page_settings',
                    'type' => 'Landing',
                    'similarity' => $this->calculateKeywordMatch($queryLower, $item->section_key . ' ' . (string) $content),
                ];
            }

            // 11. Search Curriculum Settings
            $curriculumSettings = CurriculumSetting::where(function ($q) use ($queryLower) {
                    $q->where('section_key', 'like', "%{$queryLower}%")
                      ->orWhere('content', 'like', "%{$queryLower}%");
                })
                ->limit(3)
                ->get();

            foreach ($curriculumSettings as $item) {
                $content = is_array($item->content) ? json_encode($item->content) : (string) $item->content;
                $results[] = [
                    'id' => $item->id,
                    'content' => Str::limit((string) $content, 1200),
                    'title' => 'Kurikulum: ' . $item->section_key,
                    'category' => 'Kurikulum',
                    'source' => 'database',
                    'table' => 'curriculum_settings',
                    'type' => 'Kurikulum',
                    'similarity' => $this->calculateKeywordMatch($queryLower, $item->section_key . ' ' . (string) $content),
                ];
            }

            // 12. Search School Profile Settings
            $profileSettings = SchoolProfileSetting::where(function ($q) use ($queryLower) {
                    $q->where('section_key', 'like', "%{$queryLower}%")
                      ->orWhere('content', 'like', "%{$queryLower}%");
                })
                ->limit(3)
                ->get();

            foreach ($profileSettings as $item) {
                $content = is_array($item->content) ? json_encode($item->content) : (string) $item->content;
                $results[] = [
                    'id' => $item->id,
                    'content' => Str::limit((string) $content, 1200),
                    'title' => 'Profil: ' . $item->section_key,
                    'category' => 'Profil Sekolah',
                    'source' => 'database',
                    'table' => 'school_profile_settings',
                    'type' => 'Profil Sekolah',
                    'similarity' => $this->calculateKeywordMatch($queryLower, $item->section_key . ' ' . (string) $content),
                ];
            }

            // 13. Search SPMB Settings
            $spmbSettings = SpmbSetting::where(function ($q) use ($queryLower) {
                    $q->where('section_key', 'like', "%{$queryLower}%")
                      ->orWhere('content', 'like', "%{$queryLower}%");
                })
                ->limit(3)
                ->get();

            foreach ($spmbSettings as $item) {
                $content = is_array($item->content) ? json_encode($item->content) : (string) $item->content;
                $results[] = [
                    'id' => $item->id,
                    'content' => Str::limit((string) $content, 1200),
                    'title' => 'SPMB: ' . $item->section_key,
                    'category' => 'SPMB',
                    'source' => 'database',
                    'table' => 'spmb_settings',
                    'type' => 'SPMB',
                    'similarity' => $this->calculateKeywordMatch($queryLower, $item->section_key . ' ' . (string) $content),
                ];
            }

            // 14. Search Program Studi Settings
            $prodiSettings = ProgramStudiSetting::where(function ($q) use ($queryLower) {
                    $q->where('program_name', 'like', "%{$queryLower}%")
                      ->orWhere('section_key', 'like', "%{$queryLower}%")
                      ->orWhere('content', 'like', "%{$queryLower}%");
                })
                ->limit(3)
                ->get();

            foreach ($prodiSettings as $item) {
                $content = is_array($item->content) ? json_encode($item->content) : (string) $item->content;
                $results[] = [
                    'id' => $item->id,
                    'content' => Str::limit((string) $content, 1200),
                    'title' => $item->program_name . ' - ' . $item->section_key,
                    'category' => 'Program Studi',
                    'source' => 'database',
                    'table' => 'program_studi_settings',
                    'type' => 'Program Studi',
                    'similarity' => $this->calculateKeywordMatch($queryLower, $item->program_name . ' ' . $item->section_key . ' ' . (string) $content),
                ];
            }

            // 15. Search PTN Admissions (serapan PTN)
            $ptnAdmissions = PtnAdmission::with(['university', 'batch'])
                ->where(function ($q) use ($queryLower) {
                    $q->where('program_studi', 'like', "%{$queryLower}%")
                      ->orWhereHas('university', function ($uq) use ($queryLower) {
                          $uq->where('name', 'like', "%{$queryLower}%")
                             ->orWhere('short_name', 'like', "%{$queryLower}%");
                      })
                      ->orWhereHas('batch', function ($bq) use ($queryLower) {
                          $bq->where('name', 'like', "%{$queryLower}%")
                             ->orWhere('type', 'like', "%{$queryLower}%")
                             ->orWhere('year', 'like', "%{$queryLower}%");
                      });
                })
                ->limit(5)
                ->get();

            foreach ($ptnAdmissions as $item) {
                $uni = $item->university?->name ?? '-';
                $batch = $item->batch?->name ?? '-';
                $content = "Universitas: {$uni}\nProgram Studi: {$item->program_studi}\nJumlah Siswa: {$item->count}\nBatch: {$batch}";
                $results[] = [
                    'id' => $item->id,
                    'content' => $content,
                    'title' => $uni . ' - ' . $item->program_studi,
                    'category' => 'Serapan PTN',
                    'source' => 'database',
                    'table' => 'ptn_admissions',
                    'type' => 'Serapan PTN',
                    'similarity' => $this->calculateKeywordMatch($queryLower, $content),
                ];
            }

            // 16. Search TKA Averages
            $tkaAverages = TkaAverage::where(function ($q) use ($queryLower) {
                    $q->where('academic_year', 'like', "%{$queryLower}%")
                      ->orWhere('exam_type', 'like', "%{$queryLower}%")
                      ->orWhere('subject_name', 'like', "%{$queryLower}%");
                })
                ->limit(8)
                ->get();

            foreach ($tkaAverages as $item) {
                $content = "Tahun Ajaran: {$item->academic_year}\nJenis Ujian: {$item->exam_type}\nMata Pelajaran: {$item->subject_name}\nRata-rata: {$item->average_score}";
                $results[] = [
                    'id' => $item->id,
                    'content' => $content,
                    'title' => $item->subject_name . ' - ' . $item->academic_year,
                    'category' => 'Rata-rata TKA',
                    'source' => 'database',
                    'table' => 'tka_averages',
                    'type' => 'Rata-rata TKA',
                    'similarity' => $this->calculateKeywordMatch($queryLower, $content),
                ];
            }

            // 17. Search Seragam (School Uniforms)
            $seragams = \App\Models\Seragam::where('is_active', true)
                ->where(function ($q) use ($queryLower) {
                    $q->where('name', 'like', "%{$queryLower}%")
                      ->orWhere('description', 'like', "%{$queryLower}%")
                      ->orWhere('category', 'like', "%{$queryLower}%");
                })
                ->limit(3)
                ->get();

            foreach ($seragams as $item) {
                $days = !empty($item->usage_days) ? implode(', ', $item->usage_days) : '-';
                $results[] = [
                    'id' => $item->id,
                    'content' => trim(($item->description ?? '') . "\n\nHari: {$days}\n\nAturan: " . strip_tags($item->rules ?? '-')),
                    'title' => $item->name,
                    'category' => 'Seragam Sekolah',
                    'source' => 'database',
                    'table' => 'seragams',
                    'type' => 'Seragam',
                    'similarity' => $this->calculateKeywordMatch($queryLower, $item->name . ' ' . ($item->description ?? '')),
                ];
            }

            // Sort by similarity and return top results
            usort($results, function ($a, $b) {
                return $b['similarity'] <=> $a['similarity'];
            });

            return array_slice($results, 0, $limit);
        } catch (\Exception $e) {
            Log::error('Failed to search database content', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Calculate keyword match score (simple relevance scoring)
     */
    protected function calculateKeywordMatch(string $query, string $content): float
    {
        $queryWords = array_filter(explode(' ', $query));
        $contentLower = strtolower($content);
        $matchCount = 0;

        foreach ($queryWords as $word) {
            if (strpos($contentLower, $word) !== false) {
                $matchCount += strlen($word);
            }
        }

        // Normalize score (0-1 range)
        return $matchCount > 0 ? min($matchCount / 100, 1.0) : 0.0;
    }

    /**
     * Process document: chunk and generate embeddings (using pgvector or fallback)
     */
    public function processDocument(RagDocument $document): bool
    {
        try {
            // 1. Delete existing chunks from DB
            $document->chunks()->delete();

            // 2. Split content into chunks
            $chunks = $this->splitTextIntoChunks($document->content);

            // 3. Generate embeddings and store in PostgreSQL
            foreach ($chunks as $index => $chunkText) {
                $embeddingResult = $this->embeddingService->createEmbedding($chunkText, 'passage');

                if (!$embeddingResult['success']) {
                    Log::error('Failed to generate embedding', [
                        'document_id' => $document->id,
                        'chunk_index' => $index,
                    ]);
                    continue;
                }

                $embedding = $embeddingResult['embedding'];
                $tokenCount = $this->estimateTokenCount($chunkText);

                try {
                    if ($this->pgvectorAvailable) {
                        // Store with pgvector
                        $embeddingStr = '[' . implode(',', $embedding) . ']';
                        DB::statement(
                            "INSERT INTO rag_document_chunks (document_id, content, chunk_index, token_count, embedding, created_at, updated_at)
                             VALUES (?, ?, ?, ?, ?::vector, NOW(), NOW())",
                            [$document->id, $chunkText, $index, $tokenCount, $embeddingStr]
                        );
                    } else {
                        // Store as JSON text (fallback)
                        DB::table('rag_document_chunks')->insert([
                            'document_id' => $document->id,
                            'content' => $chunkText,
                            'chunk_index' => $index,
                            'token_count' => $tokenCount,
                            'embedding' => json_encode($embedding),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                    
                    Log::info('Chunk stored successfully', [
                        'document_id' => $document->id,
                        'chunk_index' => $index,
                        'token_count' => $tokenCount,
                        'mode' => $this->pgvectorAvailable ? 'pgvector' : 'fallback',
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to store chunk', [
                        'document_id' => $document->id,
                        'chunk_index' => $index,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            }

            // 4. Mark document as processed
            $document->update(['is_processed' => true]);

            Log::info('Document processing completed', [
                'document_id' => $document->id,
                'chunks_count' => count($chunks),
                'mode' => $this->pgvectorAvailable ? 'pgvector' : 'fallback',
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Document processing failed', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Get quick reply from database for common questions
     * IMPROVED: Better keyword matching with stricter rules
     */
    protected function getDatabaseQuickReply(string $query): ?array
    {
        $query = strtolower($query);
        
        // More specific keyword matching with combined keywords
        // Format: 'key' => [primary_keywords, secondary_keywords (must be in title), category]
        $keywordMap = [
            'ppdb' => [
                'primary' => ['ppdb', 'penerimaan peserta didik baru', 'pendaftaran'],
                'title_must_contain' => ['ppdb', 'pendaftaran', 'penerimaan'],
                'category' => 'PPDB',
                'table' => 'posts'
            ],
            'biaya' => [
                'primary' => ['biaya sekolah', 'spp', 'uang sekolah', 'pembayaran'],
                'title_must_contain' => ['biaya', 'spp', 'pembayaran', 'gratis'],
                'category' => 'Informasi',
                'table' => 'posts'
            ],
            'program_studi' => [
                'primary' => ['program studi', 'peminatan', 'jurusan', 'mipa', 'ipa ', 'ipa?', 'ipa!', 'ipa,', '.ipa', 'ipa', 'ips ', 'ips?', 'ips!', 'ips,', '.ips', 'ips', 'bahasa'],
                'title_must_contain' => ['program', 'peminatan', 'jurusan', 'mipa', 'ipa', 'ips', 'bahasa'],
                'category' => null,
                'table' => 'programs'
            ],
        ];

        foreach ($keywordMap as $key => $config) {
            // Check if query contains primary keywords
            $hasPrimaryKeyword = false;
            foreach ($config['primary'] as $keyword) {
                if (str_contains($query, $keyword)) {
                    $hasPrimaryKeyword = true;
                    break;
                }
            }

            if (!$hasPrimaryKeyword) {
                continue; // Skip if no primary keyword found
            }

            try {
                if ($config['table'] === 'posts') {
                    // Search with strict title matching
                    $post = \App\Models\Post::where('status', 'published')
                        ->where(function ($q) use ($config) {
                            // Title MUST contain one of the required keywords
                            foreach ($config['title_must_contain'] as $keyword) {
                                $q->orWhere('title', 'like', "%{$keyword}%");
                            }
                        })
                        ->when($config['category'], function ($q) use ($config) {
                            $q->where('category', $config['category']);
                        })
                        ->latest()
                        ->first();

                    if ($post) {
                        $excerpt = strip_tags($post->content);
                        $excerpt = substr($excerpt, 0, 300) . '...';
                        
                        return [
                            'found' => true,
                            'source' => 'post',
                            'message' => sprintf(
                                "**%s**\n\n%s\n\n📌 Baca selengkapnya di: %s",
                                $post->title,
                                $excerpt,
                                url("/berita/{$post->slug}")
                            )
                        ];
                    }
                } elseif ($config['table'] === 'programs') {
                    $program = \App\Models\Program::where('is_featured', true)
                        ->where(function ($q) use ($config) {
                            foreach ($config['title_must_contain'] as $keyword) {
                                $q->orWhere('title', 'like', "%{$keyword}%")
                                  ->orWhere('description', 'like', "%{$keyword}%");
                            }
                        })
                        ->first();

                    if ($program) {
                        return [
                            'found' => true,
                            'source' => 'program',
                            'message' => sprintf(
                                "**%s**\n\n%s\n\n%s",
                                $program->title,
                                $program->description,
                                $program->link ? "📖 Info lengkap: " . url($program->link) : ""
                            )
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::error('Database quick reply search failed', [
                    'keyword' => $key,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Check for teacher/guru list queries
        // Hanya trigger jika query spesifik tentang guru/staff/manajemen
        $isTeacherQuery = str_contains($query, ' daftar guru') || str_contains($query, 'list guru') || 
            str_contains($query, 'siapa guru') || str_contains($query, 'siapa saja guru') ||
            str_contains($query, 'tenaga pendidik') || 
            str_contains($query, 'staff') || str_contains($query, 'tata usaha') ||
            str_contains($query, 'manajemen') || str_contains($query, 'humas') || 
            str_contains($query, 'kesiswaan') || str_contains($query, 'kurikulum') ||
            ($query === 'guru') || str_starts_with($query, 'guru ') || str_ends_with($query, ' guru');
        
        if ($isTeacherQuery) {
            return $this->getTeacherListReply($query);
        }

        // No quick reply found, let RAG/AI handle it
        return ['found' => false];
    }

    /**
     * Get teacher list reply
     */
    protected function getTeacherListReply(string $query): ?array
    {
        try {
            $queryLower = strtolower($query);
            
            // Determine what type of teacher data to retrieve
            $isStaffQuery = str_contains($queryLower, 'staff') || 
                           str_contains($queryLower, 'tata usaha') || 
                           str_contains($queryLower, 'tu') ||
                           str_contains($queryLower, 'manajemen') ||
                           str_contains($queryLower, 'humas') ||
                           str_contains($queryLower, 'kesiswaan') ||
                           str_contains($queryLower, 'kurikulum');
            $isSpecificDepartment = false;
            $departmentFilter = null;
            $departmentFilterTerms = [];

            // Subject/department aliases -> canonical department names in DB
            $departmentAliasMap = [
                'Biologi' => ['biologi'],
                'Fisika' => ['fisika'],
                'Kimia' => ['kimia'],
                'Matematika' => ['matematika'],
                'Pendidikan Pancasila' => ['pkn', 'ppkn', 'pendidikan pancasila'],
                'Pendidikan Agama Islam' => ['pai', 'pendidikan agama islam'],
                'Penjasorkes' => ['pjok', 'penjaskes', 'penjas', 'olahraga'],
                'Bimbingan Konseling' => ['bk', 'bimbingan konseling'],
                'Bahasa Indonesia' => ['bahasa indonesia', 'bhs indonesia'],
                'Bahasa Inggris' => ['bahasa inggris', 'bhs inggris', 'english'],
                'Sejarah' => ['sejarah'],
                'Geografi' => ['geografi'],
                'Ekonomi' => ['ekonomi'],
                'Sosiologi' => ['sosiologi'],
                'Kesiswaan' => ['kesiswaan'],
                'Kurikulum' => ['kurikulum'],
                'Humas' => ['humas'],
                'Perpustakaan' => ['perpus', 'perpustakaan'],
            ];

            foreach ($departmentAliasMap as $canonical => $aliases) {
                foreach ($aliases as $alias) {
                    if (str_contains($queryLower, $alias)) {
                        $departmentFilterTerms[] = $canonical;
                        break;
                    }
                }
            }

            // "Guru IPA" should return science teachers (Biologi/Fisika/Kimia/Matematika)
            if (preg_match('/\bipa\b/u', $queryLower) === 1) {
                $departmentFilterTerms = array_merge($departmentFilterTerms, ['Biologi', 'Fisika', 'Kimia', 'Matematika']);
            }

            $departmentFilterTerms = array_values(array_unique($departmentFilterTerms));
            if (!empty($departmentFilterTerms)) {
                $isSpecificDepartment = true;
                $departmentFilter = implode(', ', $departmentFilterTerms);
            }
            
            // Build query
            $teacherQuery = Teacher::where('is_active', true);
            
            if ($isStaffQuery) {
                $teacherQuery->where('type', 'staff');
            } else {
                $teacherQuery->where('type', 'guru');
            }
            
            if ($isSpecificDepartment && !empty($departmentFilterTerms)) {
                // Case-insensitive partial match for one or more department terms
                $teacherQuery->where(function ($q) use ($departmentFilterTerms) {
                    foreach ($departmentFilterTerms as $term) {
                        $q->orWhereRaw('LOWER(COALESCE(department, \'\')) LIKE ?', ['%' . strtolower($term) . '%']);
                    }
                });
            }
            
            $teachers = $teacherQuery->orderBy('position', 'asc')->orderBy('name', 'asc')->get();
            
            if ($teachers->isEmpty()) {
                return [
                    'found' => true,
                    'message' => 'Hmm, data guru/staff belum tersedia di sistem nih. Coba hubungi sekolah langsung ya untuk info lengkapnya! 📞',
                    'source' => 'database',
                ];
            }
            
            // Build response message
            $totalCount = $teachers->count();
            $typeLabel = $isStaffQuery ? 'Staff' : 'Guru';
            
            if ($isSpecificDepartment) {
                $deptDisplay = $departmentFilter;
                $message = "✨ **Daftar {$typeLabel} {$deptDisplay} SMAN 1 Baleendah** ✨\n\n";
                $message .= "Total: {$totalCount} {$typeLabel} {$deptDisplay}\n\n";
            } else {
                $message = "✨ **Daftar {$typeLabel} SMAN 1 Baleendah** ✨\n\n";
                $message .= "Total: {$totalCount} {$typeLabel}\n\n";
            }
            
            // Group by department/position for better organization
            $grouped = $teachers->groupBy('department');
            
            foreach ($grouped as $department => $group) {
                $deptName = $department ?: 'Umum';
                $message .= "📚 *{$deptName}*\n";
                
                foreach ($group->take(10) as $teacher) { // Limit 10 per department to avoid too long message
                    $position = $teacher->position ? " ({$teacher->position})" : '';
                    $message .= "• {$teacher->name}{$position}\n";
                }
                
                if ($group->count() > 10) {
                    $remaining = $group->count() - 10;
                    $message .= "• ... dan {$remaining} lainnya\n";
                }
                
                $message .= "\n";
            }
            
            $message .= "💡 *Mau tau detail lengkap tentang salah satu {$typeLabel}? Tanya aja namanya!*";
            
            return [
                'found' => true,
                'message' => $message,
                'source' => 'database',
            ];
            
        } catch (\Exception $e) {
            Log::error('Teacher list reply failed', ['error' => $e->getMessage()]);
            return ['found' => false];
        }
    }

    /**
     * Generate RAG-enhanced response
     */
    public function generateRagResponse(string $userQuery, array $conversationHistory = []): array
    {
        $startTime = microtime(true);
        $requestId = uniqid('rag_', true);
        
        Log::info('[RagService] ========== RAG REQUEST STARTED ==========', [
            'request_id' => $requestId,
            'user_query' => substr($userQuery, 0, 100),
            'history_count' => count($conversationHistory),
        ]);
        
        // 1. Check if RAG is enabled
        $ragEnabled = AiSetting::get('rag_enabled', true);
        
        Log::info('[RagService] RAG settings', [
            'request_id' => $requestId,
            'rag_enabled' => $ragEnabled,
        ]);

        if (!$ragEnabled) {
            Log::info('[RagService] RAG disabled, using simple response', ['request_id' => $requestId]);
            return $this->generateSimpleResponse($userQuery, $conversationHistory);
        }

        // 2. Guardrails: Check if query is school-related
        if (!$this->isSchoolRelatedQuery($userQuery)) {
            Log::info('[RagService] Query not school-related', ['request_id' => $requestId]);
            return [
                'success' => true,
                'message' => "Eh, maaf ya! Aku ini AI SMANSA, jadi cuma bisa ngebantu tentang SMAN 1 Baleendah aja nih 😅\n\nKalo kamu mau tanya tentang:\n• PPDB dan pendaftaran\n• Jurusan (MIPA, IPS, Bahasa)\n• Ekstrakurikuler\n• Fasilitas sekolah\n• Info akademik\n\nAku siap banget bantu! Yuk, tanya aja~ ✨",
                'is_rag_enhanced' => false,
                'retrieved_documents' => [],
            ];
        }

        // 2. Try quick database reply for common keywords (faster than full RAG search)
        $quickReply = $this->getDatabaseQuickReply($userQuery);
        if ($quickReply['found'] ?? false) {
            Log::info('[RagService] Quick reply found', [
                'request_id' => $requestId,
                'source' => $quickReply['source'] ?? 'unknown',
                'elapsed_ms' => round((microtime(true) - $startTime) * 1000),
            ]);
            return [
                'success' => true,
                'message' => $quickReply['message'],
                'is_rag_enhanced' => true,
                'retrieved_documents' => [$quickReply['source']],
            ];
        }

        // 3. Search database content (Posts, Teachers, FAQs, Programs, Extracurriculars)
        $databaseResults = $this->searchDatabaseContent($userQuery, 3);
        Log::info('[RagService] Database search results', [
            'request_id' => $requestId,
            'results_count' => count($databaseResults),
        ]);

        // 4. Retrieve relevant RAG document chunks
        $ragChunks = $this->retrieveRelevantChunks($userQuery, 3);
        Log::info('[RagService] RAG chunks retrieved', [
            'request_id' => $requestId,
            'chunks_count' => count($ragChunks),
        ]);

        // Combine results - prioritize database results, then RAG documents
        $allChunks = array_merge($databaseResults, $ragChunks);

        // Sort combined results by similarity
        usort($allChunks, function ($a, $b) {
            return ($b['similarity'] ?? 0) <=> ($a['similarity'] ?? 0);
        });

        if (empty($allChunks)) {
            Log::info('[RagService] No relevant data found, falling back to simple response', ['request_id' => $requestId]);
            return $this->generateSimpleResponse($userQuery, $conversationHistory);
        }

        // 5. Build context from combined chunks
        $context = $this->buildContextFromChunks(array_slice($allChunks, 0, 5));

        // 6. Detect which provider will be used and choose appropriate system prompt
        $useSimplePrompt = $this->shouldUseSimplePrompt();
        
        if ($useSimplePrompt) {
            // Ollama will be used - use simple prompt (better performance)
            $systemPrompt = $this->buildSimpleSystemPrompt();
            Log::info('[RagService] Using simple prompt for Ollama', ['request_id' => $requestId]);
        } else {
            // OpenAI will be used - use enhanced RAG prompt with full context
            $systemPrompt = $this->buildRagSystemPrompt($context);
            Log::info('[RagService] Using RAG-enhanced prompt for OpenAI', [
                'request_id' => $requestId,
                'context_length' => strlen($context),
            ]);
        }

        // 7. Prepare messages for chat completion
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        // Add conversation history
        foreach ($conversationHistory as $msg) {
            $messages[] = [
                'role' => $msg['sender'] === 'user' ? 'user' : 'assistant',
                'content' => $msg['message'],
            ];
        }

        // Add current query
        $messages[] = ['role' => 'user', 'content' => $userQuery];
        
        Log::info('[RagService] Calling GroqService::chatCompletion', [
            'request_id' => $requestId,
            'num_messages' => count($messages),
            'system_prompt_length' => strlen($systemPrompt),
        ]);

        // 7. Generate response
        $completionResult = $this->groq->chatCompletion($messages);
        
        $totalElapsed = round((microtime(true) - $startTime) * 1000);
        
        Log::info('[RagService] OpenAI completion result', [
            'request_id' => $requestId,
            'success' => $completionResult['success'] ?? false,
            'provider' => $completionResult['provider'] ?? 'unknown',
            'message_length' => strlen($completionResult['message'] ?? ''),
            'message_preview' => substr($completionResult['message'] ?? '', 0, 100),
            'total_elapsed_ms' => $totalElapsed,
        ]);

        if (!$completionResult['success']) {
            Log::error('[RagService] Completion failed', [
                'request_id' => $requestId,
                'error' => $completionResult['error'] ?? 'unknown',
            ]);
            return [
                'success' => false,
                'message' => 'Maaf, terjadi kesalahan saat memproses pertanyaan Anda. Silakan coba lagi.',
                'is_rag_enhanced' => false,
                'retrieved_documents' => [],
            ];
        }

        // 8. Post-filter: Validate response is still school-related
        if ($this->isNonSchoolResponse($completionResult['message'])) {
            return [
                'success' => true,
                'message' => 'Maaf, saya only dapat menjawab pertanyaan tentang SMAN 1 Baleendah. Untuk informasi lain, silakan cek sumber resmi atau tanyakan hal yang terkait sekolah.',
                'is_rag_enhanced' => false,
                'retrieved_documents' => [],
            ];
        }

        // 9. Extract document IDs for logging
        $documentIds = array_unique(array_column($allChunks, 'document_id'));

        return [
            'success' => true,
            'message' => $completionResult['message'],
            'is_rag_enhanced' => true,
            'retrieved_documents' => $documentIds,
            'context_chunks' => array_slice($allChunks, 0, 5), // Return top 5 chunks used
        ];
    }

    /**
     * Generate simple response without RAG
     */
    protected function generateSimpleResponse(string $userQuery, array $conversationHistory = []): array
    {
        // Try quick database reply first (faster response)
        $quickReply = $this->getDatabaseQuickReply($userQuery);
        if ($quickReply['found'] ?? false) {
            return [
                'success' => true,
                'message' => $quickReply['message'],
                'is_rag_enhanced' => true,
                'retrieved_documents' => [$quickReply['source']],
            ];
        }

        $systemPrompt = "Kamu adalah AI SMANSA, teman ngobrol santai dan asik dari SMAN 1 Baleendah. Gaya bicara kamu kayak teman SMA yang friendly dan kekinian! 🎉

📍 INFO PENTING SMANSA:
- Alamat: Jl. R.A.A. Wiranatakoesoemah No.30, Baleendah, Bandung 40375
- Biaya: GRATIS 100%! (SMA Negeri)
- Jurusan: MIPA, IPS, Bahasa
- PPDB: Ada jalur zonasi, prestasi, afirmasi, perpindahan tugas

GAYA NGOMONG KAMU:
1. Selalu sapa dulu dengan ramah (pake 'Halo!', 'Hai!', 'Eh!', dll)
2. Bicara dengan santai dan natural, jangan kaku kayak robot
3. Pake emoji biar hidup dan seru ✨
4. Panjang jawaban yang pas - jangan terlalu pendek, jangan terlalu panjang
5. Kalo info kurang lengkap, bilang dengan baik dan kasih solusi alternatif
6. Tunjukkan antusiasme! Semangatin user yang nanya
7. Tutup dengan nawarin bantuan lagi, misal 'Ada yang mau ditanya lagi?' atau 'Mau tau info lain?'
8. Kalo ditanya hal di luar sekolah, jawab dengan baik sambil arahin ke topik sekolah
9. Sebut 'SMANSA' dengan bangga! 🏫

Contoh gaya jawab:
- 'Halo! Mau daftar SMANSA ya? Keren banget! Yuk, aku jelasin caranya...'
- 'Wah, info lengkapnya aku belum ada nih. Tapi tenang, kamu bisa hubungi sekolah langsung ya!'

Jawab dengan gaya yang asik dan ngenakin! 😊";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        // Build conversation history with validation and limit
        // Only keep last 5 exchanges (10 messages max) to prevent context overflow
        $recentHistory = array_slice($conversationHistory, -10);
        
        foreach ($recentHistory as $msg) {
            // Skip invalid messages
            if (!isset($msg['sender']) || !isset($msg['message'])) {
                continue;
            }
            
            // Ensure message content is string
            $content = is_string($msg['message']) ? $msg['message'] : (string) $msg['message'];
            
            if (empty($content)) {
                continue;
            }
            
            // Truncate very long messages to prevent context overflow
            if (strlen($content) > 2000) {
                $content = substr($content, 0, 2000) . '... (dipotong karena terlalu panjang)';
            }

            $messages[] = [
                'role' => $msg['sender'] === 'user' ? 'user' : 'assistant',
                'content' => $content,
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $userQuery];

        Log::info('[RagService] Calling OpenAI Service', [
            'query' => $userQuery, 
            'num_messages' => count($messages),
            'messages_preview' => array_map(fn($m) => ['role' => $m['role'], 'length' => strlen($m['content'])], $messages)
        ]);

        $completionResult = $this->groq->chatCompletion($messages);

        Log::info('[RagService] OpenAI Result', [
            'success' => $completionResult['success'] ?? false,
            'has_message' => isset($completionResult['message']) && !empty($completionResult['message']),
            'has_error' => isset($completionResult['error']) && !empty($completionResult['error']),
            'message_preview' => isset($completionResult['message']) ? substr($completionResult['message'], 0, 100) : 'EMPTY',
        ]);

        if (!$completionResult['success'] && isset($completionResult['error'])) {
            Log::error('[RagService] OpenAI Error', ['error' => $completionResult['error']]);
        }

        // Ensure message is not empty
        $message = $completionResult['message'] ?? '';
        if (empty($message)) {
            $message = 'Maaf, saya tidak dapat memproses pertanyaan Anda saat ini. Silakan coba lagi.';
            Log::warning('[RagService] Empty message from AI, using fallback');
        }

        return [
            'success' => $completionResult['success'],
            'message' => $message,
            'is_rag_enhanced' => false,
            'retrieved_documents' => [],
        ];
    }

    /**
     * Split text into chunks
     */
    protected function splitTextIntoChunks(string $text): array
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $chunks = [];
        $currentChunk = '';
        $currentTokens = 0;

        foreach ($sentences as $sentence) {
            $sentenceTokens = $this->estimateTokenCount($sentence);

            if ($currentTokens + $sentenceTokens > $this->chunkSize && !empty($currentChunk)) {
                $chunks[] = trim($currentChunk);
                // Keep overlap
                $overlapText = $this->getLastNTokens($currentChunk, $this->chunkOverlap);
                $currentChunk = $overlapText . ' ' . $sentence;
                $currentTokens = $this->estimateTokenCount($currentChunk);
            } else {
                $currentChunk .= ' ' . $sentence;
                $currentTokens += $sentenceTokens;
            }
        }

        if (!empty($currentChunk)) {
            $chunks[] = trim($currentChunk);
        }

        return $chunks;
    }

    /**
     * Estimate token count (rough approximation)
     */
    protected function estimateTokenCount(string $text): int
    {
        // Rough estimate: 1 token ≈ 4 characters
        return (int) ceil(strlen($text) / 4);
    }

    /**
     * Get last N tokens from text
     */
    protected function getLastNTokens(string $text, int $n): string
    {
        $words = explode(' ', $text);
        $lastWords = array_slice($words, -$n);
        return implode(' ', $lastWords);
    }

    /**
     * Determine if we should use simple prompt or enhanced RAG prompt
     * With Groq, always use enhanced prompt since it's a capable cloud model
     */
    protected function shouldUseSimplePrompt(): bool
    {
        // Groq models are capable enough for enhanced RAG prompts
        return false;
        return true;
    }

    /**
     * Build simple system prompt (for Ollama - lightweight)
     */
    protected function buildSimpleSystemPrompt(): string
    {
        return "Kamu adalah AI SMANSA, teman ngobrol santai dari SMAN 1 Baleendah. Bicara dengan gaya kekinian, friendly, dan kayak lagi chat sama teman!

📍 INFO PENTING SMANSA:
- Alamat: Jl. R.A.A. Wiranatakoesoemah No.30, Baleendah, Bandung 40375
- Biaya: GRATIS! (SMA Negeri)
- Jurusan: MIPA, IPS, Bahasa
- PPDB: Ada jalur zonasi, prestasi, afirmasi, sama perpindahan tugas orang tua

GAYA NGOMONG KAMU:
1. Pake bahasa sehari-hari yang asik, gak kaku
2. Boleh pake emoji biar lebih hidup dan seru ✨
3. Selalu sapa dulu, misal \"Halo!\" atau \"Hai!\"
4. Jelasin dengan panjang yang pas - jangan terlalu pendek, jangan terlalu panjang
5. Kalo info kurang lengkap, bilang dengan sopan dan kasih solusi
6. Tunjukkan semangat dan antusiasme! Jangan monotone
7. Selalu tutup dengan kalimat yang ngebantu user, misal \"Ada yang mau ditanya lagi?\" atau \"Mau tau info lain nggak?\"
8. Kalo ditanya hal di luar sekolah, jelasin dengan baik sambil arahin ke topik sekolah
9. Jawab dengan natural, kayak lagi ngobrol langsung

CONTOH GAYA JAWAB:
- \"Halo! Mau daftar SMANSA ya? Keren! Yuk, aku jelasin caranya...\"
- \"Wah, info lengkapnya belum ada nih. Tapi tenang, kamu bisa hubungi sekolah langsung ya!\"

Yuk, jawab dengan gaya yang friendly dan ngenakin! 😊";
    }

    /**
     * Build context from retrieved chunks
     */
    protected function buildContextFromChunks(array $chunks): string
    {
        $contextParts = [];

        foreach ($chunks as $chunk) {
            $contextParts[] = sprintf(
                "[Dokumen: %s | Kategori: %s]\n%s",
                $chunk['document_title'] ?? $chunk['title'] ?? 'Informasi Sekolah',
                $chunk['category'] ?? 'Umum',
                $chunk['content']
            );
        }

        return implode("\n\n---\n\n", $contextParts);
    }

    /**
     * Build RAG system prompt
     */
    protected function buildRagSystemPrompt(string $context): string
    {
        return <<<PROMPT
Hai! Aku AI SMANSA, teman virtual yang siap nemenin kamu ngobrol tentang SMAN 1 Baleendah! 😊

Berikut info yang aku punya buat kamu:
{$context}

CARA JAWABKU:
1. Sapa dulu dengan ramah dan hangat (pake "Halo", "Hai", "Eh", dll)
2. Jawab berdasarkan info di atas, tapi dengan gaya ngobrol santai
3. Pake emoji biar seru dan gak membosankan ✨
4. Kalo ada info yang kurang, bilang dengan baik dan kasih saran alternatif
5. Jangan terlalu kaku atau formal kayak robot
6. Tutup dengan ajakan buat nanya lagi atau kasih info tambahan yang mungkin berguna
7. Sebut "SMANSA" dengan bangga! Ini sekolah keren lho

📍 INFO SINGKAT SMANSA:
- Alamat: Jl. R.A.A. Wiranatakoesoemah No.30, Baleendah, Bandung 40375
- Biaya: GRATIS! (SMA Negeri Jawa Barat)
- Jurusan: MIPA, IPS, Bahasa

Contoh gaya jawab:
- "Halo! Mau tau tentang PPDB ya? Oke, aku jelasin ya..."
- "Wah, info lengkapnya aku belum punya nih. Tapi kamu bisa tanya langsung ke sekolah, mereka pasti bantu!"

Yuk, jawab dengan gaya yang asik dan ngenakin! 🎉
PROMPT;
    }

    /**
     * Check if query is asking for teacher list
     * COMPREHENSIVE: Avoid conflicts with program-related queries
     */
    protected function isTeacherListQuery(string $query): bool
    {
        $queryLower = strtolower($query);

        // Check for specific teacher list patterns FIRST (higher priority)
        $teacherListPatterns = [
            'daftar guru', 'list guru', 'data guru', 'nama guru',
            'siapa saja guru', 'siapa-siapa guru', 'semua guru',
            'daftar staff', 'list staff', 'data staff',
            'siapa saja staff', 'semua staff',
            'tenaga pendidik', 'tata usaha', 'kepala sekolah',
            'wakasek', 'wakil kepala',
            'jumlah guru', 'total guru', 'berapa guru',
            'manajemen sekolah', 'staff manajemen', 'tim manajemen',
            'humas', 'kesiswaan', 'kurikulum', 'sarana prasarana',
        ];

        // Check if query has teacher intent with subject/department
        // e.g., "guru biologi", "guru matematika", "staff perpus"
        $hasTeacherKeyword = str_contains($queryLower, 'guru') || 
                            str_contains($queryLower, 'staff') ||
                            str_contains($queryLower, 'pengajar');
        
        $hasListIndicator = str_contains($queryLower, 'siapa') ||
                           str_contains($queryLower, 'daftar') ||
                           str_contains($queryLower, 'list') ||
                           str_contains($queryLower, 'semua');
        
        // If query has teacher keyword + list intent, it's a teacher query
        // This catches queries like "guru biologi", "guru matematika", etc.
        if ($hasTeacherKeyword && $hasListIndicator) {
            return true;
        }

        // EXCLUDE pure program-related queries (without teacher keywords)
        // These should go to Program/ProgramStudi search instead
        $programIndicators = [
            'jurusan', 'peminatan', 'program ', 'prodi', 'saintek', 'soshum',
            'laboratorium', 'lab '
        ];

        foreach ($programIndicators as $indicator) {
            if (str_contains($queryLower, $indicator)) {
                return false;
            }
        }

        // Check for isolated "guru" or "staff" with list intent
        $listIndicators = ['daftar', 'list', 'siapa saja', 'siapa-siapa', 'semua', 'total', 'jumlah', 'berapa'];
        $hasListIndicator = false;
        foreach ($listIndicators as $indicator) {
            if (str_contains($queryLower, $indicator)) {
                $hasListIndicator = true;
                break;
            }
        }

        // Only if combined with teacher keyword AND no program keywords
        if ($hasListIndicator) {
            $teacherKeywords = ['guru', 'pengajar', 'staff', 'tu ', 'tu,', 'tu?', 'tu!', 'manajemen', 'humas', 'kesiswaan', 'kurikulum'];
            foreach ($teacherKeywords as $keyword) {
                if (str_contains($queryLower, $keyword)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if query is about program studi (MIPA/IPS/Bahasa)
     */
    protected function isProgramStudiQuery(string $query): bool
    {
        $queryLower = strtolower($query);

        $programKeywords = [
            'mipa', 'ipa ', ' ipa', 'matematika', 'fisika', 'kimia', 'biologi',
            'ips ', ' ips', 'sosiologi', 'geografi', 'ekonomi', 'sejarah',
            'bahasa', 'sastra', 'bhs ', 'bhs.', 'inggris', 'indonesia', 'jepang',
            'jurusan', 'peminatan', 'program studi', 'prodi',
            'saintek', 'soshum', 'soshum'
        ];

        foreach ($programKeywords as $keyword) {
            if (str_contains($queryLower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if query is school-related (guardrails)
     */
    protected function isSchoolRelatedQuery(string $query): bool
    {
        $queryLower = mb_strtolower($query);
        
        // Block known prompt injection attempts or jailbreak patterns
        $blockedPatterns = [
            'ignore previous instructions',
            'ignore all instructions',
            'system prompt',
            'dan/jailbreak',
            'jailbreak',
            'forget everything',
            'new rules',
            'hypothetical scenario',
            'acting as',
            'developer mode',
        ];

        foreach ($blockedPatterns as $pattern) {
            if (str_contains($queryLower, $pattern)) {
                Log::warning('[RagService] Potential prompt injection detected', ['query' => $query]);
                return false;
            }
        }

        // List of allowed topics/keywords - EXPANDED for more lenient filtering
        $allowedTopics = [
            // School names
            'sman', 'sekolah', 'baleendah', 'smansa', 'sma', 'negeri', 'man',
            // Registration & Admission
            'ppdb', 'pendaftaran', 'daftar', 'online', 'offline', 'cara daftar', 
            'cara mendaftar', 'syarat', 'dokumen', 'berkas', 'verifikasi', 'zonasi',
            'afirmasi', 'prestasi', 'mutasi', 'jalur', 'kuota', 'daya tampung',
            // Students & Teachers
            'siswa', 'guru', 'murid', 'pengajar', 'tenaga pendidik', 'kepala sekolah', 'wakasek',
            // Academic
            'pelajaran', 'kurikulum', 'mata pelajaran', 'mapel', 'kelas', 'rapor', 'nilai',
            'ujian', 'pts', 'pas', 'pat', 'usbn', 'ujian nasional', 'tka', 'try out',
            'semester', 'tahun ajaran', 'libur', 'kenaikan kelas', 'kelulusan',
            // Programs/Subjects
            'mipa', 'ips', 'bahasa', 'ipa', 'matematika', 'fisika', 'kimia', 'biologi',
            'ekonomi', 'sosiologi', 'geografi', 'sejarah', 'bimbingan konseling', 'bk',
            // Extracurricular
            'eskul', 'ekstrakurikuler', 'organisasi', 'osis', 'pramuka', 'paskibra',
            'pmr', 'pecinta alam', 'rohis', 'karya ilmiah', 'kir', 'debat', 'english club',
            'basket', 'voli', 'futsal', 'badmin', 'tenis meja', 'silat', 'taekwondo',
            'musik', 'paduan suara', 'tari', 'teater', 'fotografi', 'roboitk',
            // Schedule & Events
            'jadwal', 'kalender', 'akademik', 'waktu', 'jam', 'hari', 'senin', 'selasa',
            'rabu', 'kamis', 'jumat', 'sabtu', 'minggu',
            // Achievements
            'prestasi', 'lomba', 'juara', 'peringkat', 'medali', 'penghargaan', 'penghargaan',
            // Alumni & Further Education
            'alumni', 'lulusan', 'angkatan', 'ptn', 'perguruan tinggi', 'universitas',
            'institut', 'politeknik', 'snbp', 'snbt', 'sbmptn', 'mandiri', 'kuliah',
            // Location & Contact
            'lokasi', 'alamat', 'kontak', 'telepon', 'wa', 'whatsapp', 'email', 'media sosial',
            'instagram', 'facebook', 'twitter', 'maps', 'rute', ' Transportasi', 'angkot',
            // Fees & Costs
            'biaya', 'gratis', 'bayar', 'uang', 'spp', 'iuran', 'bantuan', 'beasiswa', 'kjp',
            // Facilities
            'perpustakaan', 'fasilitas', 'kantin', 'lapangan', 'parkir', 'seragam',
            'atribut', 'laboratorium', 'lab', 'komputer', 'perkantoran', 'toilet', 'mushola',
            'masjid', 'aula', 'koperasi', 'kantin', 'pos keamanan', 'security', 'parkiran',
            // Uniform & Attributes
            'seragam', 'atribut', 'dasi', 'topi', 'logo', 'lambang', 'warna', 'putih', 'abu',
            // General inquiries
            'halo', 'hai', 'hello', 'hi', 'selamat', 'pagi', 'siang', 'sore', 'malam',
            'siapa', 'apa', 'apa itu', 'bantu', 'bantuan', 'tolong', 'tanya', 'bertanya',
            'bagaimana', 'gimana', 'cara', 'info', 'informasi', 'detail', 'jelaskan', 'penjelasan',
            'kenapa', 'mengapa', 'kapan', 'dimana', 'di mana', 'berapa', 'siapa', 'mau', 'ingin',
            'bisa', 'boleh', 'ya', 'tidak', 'ok', 'oke', 'baik', 'terima kasih', 'thanks', 'makasih',
            'sama-sama', 'sama sama', 'oke', 'sip', 'mantap', 'keren', 'bagus',
        ];

        foreach ($allowedTopics as $topic) {
            if (str_contains($queryLower, $topic)) {
                return true;
            }
        }
        
        // If query is short, likely a simple greeting or single-word question - allow it
        if (strlen($query) < 20) {
            return true;
        }

        // For longer queries, check if any word might be school-related
        // Split query and check each word
        $words = preg_split('/\s+/', $queryLower);
        $genericWords = ['yang', 'dan', 'atau', 'dengan', 'untuk', 'dari', 'pada', 'dalam', 'saya', 'aku', 'kamu', 'kita'];
        $meaningfulWords = array_diff($words, $genericWords);
        
        // If query has meaningful content but not caught by allowed topics, 
        // allow it and let the AI handle it gracefully
        if (count($meaningfulWords) > 0 && strlen($query) < 100) {
            Log::info('[RagService] Query allowed despite no keyword match', ['query' => $query]);
            return true;
        }

        return false; 
    }

    /**
     * Check if response is non-school related (post-filter)
     */
    protected function isNonSchoolResponse(string $response): bool
    {
        $nonSchoolIndicators = [
            'saya tidak bisa menjawab apapun tentang sekolah',
            'tidak memiliki informasi tentang sekolah',
            'asisten ai umum',
            'i am a large language model',
            'as an ai language model',
        ];

        $responseLower = mb_strtolower($response);
        
        foreach ($nonSchoolIndicators as $indicator) {
            if (str_contains($responseLower, $indicator)) {
                return true;
            }
        }

        return false;
    }
}

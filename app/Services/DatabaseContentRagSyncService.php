<?php

namespace App\Services;

use App\Jobs\ProcessRagDocument;
use App\Models\AcademicCalendarContent;
use App\Models\Alumni;
use App\Models\CurriculumSetting;
use App\Models\Extracurricular;
use App\Models\Faq;
use App\Models\Gallery;
use App\Models\LandingPageSetting;
use App\Models\Post;
use App\Models\Program;
use App\Models\ProgramStudiSetting;
use App\Models\PtnAdmission;
use App\Models\RagDocument;
use App\Models\SchoolProfileSetting;
use App\Models\Seragam;
use App\Models\SiteSetting;
use App\Models\SpmbSetting;
use App\Models\Teacher;
use App\Models\TkaAverage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DatabaseContentRagSyncService
{
    public const MODEL_MAP = [
        'site_settings' => SiteSetting::class,
        'posts' => Post::class,
        'faqs' => Faq::class,
        'programs' => Program::class,
        'extracurriculars' => Extracurricular::class,
        'teachers' => Teacher::class,
        'alumni' => Alumni::class,
        'galleries' => Gallery::class,
        'academic_calendar_contents' => AcademicCalendarContent::class,
        'landing_page_settings' => LandingPageSetting::class,
        'curriculum_settings' => CurriculumSetting::class,
        'school_profile_settings' => SchoolProfileSetting::class,
        'spmb_settings' => SpmbSetting::class,
        'program_studi_settings' => ProgramStudiSetting::class,
        'ptn_admissions' => PtnAdmission::class,
        'tka_averages' => TkaAverage::class,
        'seragams' => Seragam::class,
    ];

    public function syncModel(Model $model, bool $dispatchProcessing = true, bool $processInline = false): ?RagDocument
    {
        if (!$this->isSupportedModel($model::class)) {
            return null;
        }

        if (!$this->shouldIndex($model)) {
            $this->removeModel($model);
            return null;
        }

        $payload = $this->buildPayload($model);
        if (empty($payload['content'])) {
            return null;
        }

        $source = $this->buildSource($model::class, $model->getKey());

        $ragDocument = RagDocument::updateOrCreate(
            ['source' => $source],
            [
                'title' => $payload['title'],
                'content' => $payload['content'],
                'excerpt' => Str::limit(strip_tags($payload['content']), 300),
                'file_type' => 'database',
                'category' => $payload['category'],
                'metadata' => [
                    'table' => $model->getTable(),
                    'model' => $model::class,
                    'source_id' => (string) $model->getKey(),
                    'synced_at' => now()->toIso8601String(),
                ],
                'is_active' => true,
                'is_processed' => false,
            ]
        );

        if ($dispatchProcessing) {
            ProcessRagDocument::dispatch($ragDocument);
        } elseif ($processInline) {
            app(RagService::class)->processDocument($ragDocument);
        }

        return $ragDocument;
    }

    public function removeModel(Model $model): void
    {
        $this->removeByClassAndId($model::class, $model->getKey());
    }

    public function removeByClassAndId(string $modelClass, mixed $id): void
    {
        $source = $this->buildSource($modelClass, $id);
        $ragDocument = RagDocument::where('source', $source)->first();

        if ($ragDocument) {
            $ragDocument->chunks()->delete();
            $ragDocument->delete();
        }
    }

    public function reindexAll(?string $table = null, bool $dispatchProcessing = true, bool $processInline = false): array
    {
        $tables = $table ? [$table => self::MODEL_MAP[$table] ?? null] : self::MODEL_MAP;

        $total = 0;
        $synced = 0;
        $skipped = 0;

        foreach ($tables as $modelClass) {
            if (!$modelClass) {
                continue;
            }

            $query = $this->buildBaseQuery($modelClass);
            $query->chunkById(100, function ($rows) use (&$total, &$synced, &$skipped, $dispatchProcessing, $processInline) {
                foreach ($rows as $row) {
                    $total++;
                    $res = $this->syncModel($row, $dispatchProcessing, $processInline);
                    if ($res) {
                        $synced++;
                    } else {
                        $skipped++;
                    }
                }
            });
        }

        return compact('total', 'synced', 'skipped');
    }

    public function isSupportedModel(string $modelClass): bool
    {
        return in_array($modelClass, array_values(self::MODEL_MAP), true);
    }

    protected function buildSource(string $modelClass, mixed $id): string
    {
        return 'model:' . class_basename($modelClass) . ':' . (string) $id;
    }

    protected function shouldIndex(Model $model): bool
    {
        return match ($model::class) {
            Post::class => $model->status === 'published',
            Faq::class => (bool) $model->is_published,
            Extracurricular::class => (bool) $model->is_active,
            Teacher::class => (bool) $model->is_active,
            Alumni::class => (bool) $model->is_published,
            AcademicCalendarContent::class => (bool) $model->is_active,
            Seragam::class => (bool) $model->is_active,
            default => true,
        };
    }

    protected function buildBaseQuery(string $modelClass)
    {
        return match ($modelClass) {
            Post::class => Post::query()->where('status', 'published')->orderBy('id'),
            Faq::class => Faq::query()->where('is_published', true)->orderBy('id'),
            Extracurricular::class => Extracurricular::query()->where('is_active', true)->orderBy('id'),
            Teacher::class => Teacher::query()->where('is_active', true)->orderBy('id'),
            Alumni::class => Alumni::query()->where('is_published', true)->orderBy('id'),
            AcademicCalendarContent::class => AcademicCalendarContent::query()->where('is_active', true)->orderBy('id'),
            PtnAdmission::class => PtnAdmission::query()->with(['university', 'batch'])->orderBy('id'),
            Seragam::class => Seragam::query()->where('is_active', true)->orderBy('sort_order'),
            default => $modelClass::query()->orderBy('id'),
        };
    }

    protected function buildPayload(Model $model): array
    {
        return match ($model::class) {
            SiteSetting::class => [
                'title' => 'Site Setting: ' . ($model->section_key ?? 'general'),
                'category' => 'Site Settings',
                'content' => $this->jsonToText($model->content),
            ],
            Post::class => [
                'title' => (string) $model->title,
                'category' => (string) ($model->category ?? 'Post'),
                'content' => trim((string) ($model->excerpt ?? '') . "\n\n" . strip_tags((string) ($model->content ?? ''))),
            ],
            Faq::class => [
                'title' => (string) $model->question,
                'category' => 'FAQ',
                'content' => trim("Q: {$model->question}\nA: {$model->answer}"),
            ],
            Program::class => [
                'title' => (string) $model->title,
                'category' => (string) ($model->category ?? 'Program'),
                'content' => trim((string) ($model->description ?? '')),
            ],
            Extracurricular::class => [
                'title' => (string) $model->name,
                'category' => 'Extracurricular',
                'content' => trim((string) ($model->description ?? '') . "\n" . (string) ($model->activity_description ?? '')),
            ],
            Teacher::class => [
                'title' => (string) $model->name,
                'category' => $model->type === 'staff' ? 'Staff/Tata Usaha' : 'Guru',
                'content' => $this->buildTeacherContent($model),
            ],
            Alumni::class => [
                'title' => (string) $model->name,
                'category' => 'Alumni',
                'content' => trim((string) ($model->testimonial ?? '') . "\nAngkatan: " . (string) ($model->graduation_year ?? '')),
            ],
            Gallery::class => [
                'title' => (string) $model->title,
                'category' => 'Gallery',
                'content' => trim((string) ($model->description ?? '') . "\nKategori: " . (string) ($model->category ?? '')),
            ],
            AcademicCalendarContent::class => [
                'title' => (string) $model->title,
                'category' => 'Academic Calendar',
                'content' => trim('Semester: ' . (string) ($model->semester_name ?? '') . "\nTahun Ajaran: " . (string) ($model->academic_year ?? '')),
            ],
            LandingPageSetting::class => [
                'title' => 'Landing: ' . (string) $model->section_key,
                'category' => 'Landing',
                'content' => $this->jsonToText($model->content),
            ],
            CurriculumSetting::class => [
                'title' => 'Curriculum: ' . (string) $model->section_key,
                'category' => 'Curriculum',
                'content' => $this->jsonToText($model->content),
            ],
            SchoolProfileSetting::class => [
                'title' => 'School Profile: ' . (string) $model->section_key,
                'category' => 'School Profile',
                'content' => $this->jsonToText($model->content),
            ],
            SpmbSetting::class => [
                'title' => $this->formatSpmbTitle($model),
                'category' => 'PPDB / SPMB',
                'content' => $this->buildSpmbContent($model),
            ],
            ProgramStudiSetting::class => [
                'title' => $this->formatProgramStudiTitle($model),
                'category' => 'Program Studi',
                'content' => $this->buildProgramStudiContent($model),
            ],
            PtnAdmission::class => [
                'title' => (string) ($model->university?->name ?? '-') . ' - ' . (string) ($model->program_studi ?? ''),
                'category' => 'PTN Admission',
                'content' => trim('Universitas: ' . (string) ($model->university?->name ?? '-')
                    . "\nProgram Studi: " . (string) ($model->program_studi ?? '-')
                    . "\nJumlah: " . (string) ($model->count ?? 0)
                    . "\nBatch: " . (string) ($model->batch?->name ?? '-')),
            ],
            TkaAverage::class => [
                'title' => (string) ($model->subject_name ?? '-') . ' - ' . (string) ($model->academic_year ?? ''),
                'category' => 'TKA Average',
                'content' => trim('Tahun Ajaran: ' . (string) ($model->academic_year ?? '-')
                    . "\nJenis Ujian: " . (string) ($model->exam_type ?? '-')
                    . "\nMapel: " . (string) ($model->subject_name ?? '-')
                    . "\nRata-rata: " . (string) ($model->average_score ?? '-')),
            ],
            Seragam::class => [
                'title' => 'Seragam: ' . $model->name,
                'category' => 'Seragam Sekolah',
                'content' => $this->buildSeragamContent($model),
            ],
            default => [
                'title' => class_basename($model::class) . ' #' . $model->getKey(),
                'category' => class_basename($model::class),
                'content' => $this->jsonToText($model->toArray()),
            ],
        };
    }

    protected function jsonToText(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_array($value) || $value instanceof Collection) {
            return $this->convertArrayToReadableText($value);
        }

        return (string) $value;
    }

    /**
     * Convert nested array/JSON to human-readable text for RAG
     */
    protected function convertArrayToReadableText(array $data, int $depth = 0): string
    {
        $lines = [];
        $indent = str_repeat('  ', $depth);

        foreach ($data as $key => $value) {
            // Skip empty values and metadata
            if (empty($value) || in_array($key, ['image_url', 'thumbnail_url', 'icon', 'id', 'created_at', 'updated_at'])) {
                continue;
            }

            // Format key for readability
            $readableKey = $this->formatKeyForReading($key);

            if (is_array($value)) {
                // Handle indexed arrays (lists)
                if (array_is_list($value)) {
                    if (!empty($value)) {
                        $lines[] = "{$indent}{$readableKey}:";
                        foreach ($value as $index => $item) {
                            if (is_array($item)) {
                                $lines[] = $this->convertArrayToReadableText($item, $depth + 1);
                            } else {
                                $lines[] = "{$indent}  - {$item}";
                            }
                        }
                    }
                } else {
                    // Handle associative arrays
                    $nested = $this->convertArrayToReadableText($value, $depth + 1);
                    if (!empty(trim($nested))) {
                        $lines[] = "{$indent}{$readableKey}:";
                        $lines[] = $nested;
                    }
                }
            } elseif (is_string($value) && !empty(trim($value))) {
                // Handle special fields with HTML
                if (str_contains($key, 'html') || str_contains($key, 'description')) {
                    $cleanValue = strip_tags($value);
                    $lines[] = "{$indent}{$readableKey}: {$cleanValue}";
                } else {
                    $lines[] = "{$indent}{$readableKey}: {$value}";
                }
            } elseif (is_bool($value)) {
                $lines[] = "{$indent}{$readableKey}: " . ($value ? 'Ya' : 'Tidak');
            } elseif (is_numeric($value)) {
                $lines[] = "{$indent}{$readableKey}: {$value}";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Format array key to readable text
     */
    protected function formatKeyForReading(string $key): string
    {
        // Special key mappings
        $mappings = [
            'title' => 'Judul',
            'description' => 'Deskripsi',
            'desc' => 'Keterangan',
            'content' => 'Konten',
            'items' => 'Daftar Item',
            'requirements' => 'Persyaratan',
            'main_title' => 'Judul Utama',
            'main_description' => 'Deskripsi Utama',
            'quote' => 'Kutipan',
            'name' => 'Nama',
            'label' => 'Label',
            'quota' => 'Kuota',
            'date' => 'Tanggal',
        ];

        if (isset($mappings[$key])) {
            return $mappings[$key];
        }

        // Default: convert snake_case to Title Case
        return ucwords(str_replace(['_', '-'], ' ', $key));
    }

    /**
     * Format SPMB title with aliases for searchability
     */
    protected function formatSpmbTitle($model): string
    {
        $sectionMappings = [
            'pengaturan_umum' => 'PPDB - Informasi Umum',
            'jalur_pendaftaran' => 'PPDB - Jalur Pendaftaran',
            'jadwal_penting' => 'PPDB - Jadwal Penting',
            'faq' => 'PPDB - FAQ',
            'persyaratan' => 'PPDB - Persyaratan',
        ];

        return $sectionMappings[$model->section_key] ?? 'PPDB - ' . $this->formatKeyForReading($model->section_key);
    }

    /**
     * Build rich content for SPMB/PPDB
     */
    protected function buildSpmbContent($model): string
    {
        $lines = [];

        // Add PPDB aliases for searchability
        $lines[] = "Informasi PPDB (Penerimaan Peserta Didik Baru) / SPMB SMAN 1 Baleendah";
        $lines[] = "";

        // Section-specific context
        if ($model->section_key === 'jalur_pendaftaran') {
            $lines[] = "Terdapat beberapa jalur pendaftaran: Zonasi, Prestasi, Afirmasi, dan Perpindahan Tugas.";
            $lines[] = "";
        } elseif ($model->section_key === 'persyaratan') {
            $lines[] = "Persyaratan umum PPDB: Ijazah/SKL, Akta Kelahiran, Kartu Keluarga (minimal 1 tahun), Buku Rapor semester 1-5.";
            $lines[] = "";
        } elseif ($model->section_key === 'faq') {
            $lines[] = "Pertanyaan yang sering ditanyakan tentang PPDB.";
            $lines[] = "";
        }

        // Convert JSON content
        if (!empty($model->content)) {
            $contentArray = is_string($model->content) ? json_decode($model->content, true) : $model->content;
            if (is_array($contentArray)) {
                $lines[] = $this->convertArrayToReadableText($contentArray);
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Format program studi title for better searchability
     */
    protected function formatProgramStudiTitle($model): string
    {
        $programName = strtoupper($model->program_name);
        $aliases = [
            'MIPA' => 'MIPA (Matematika Ilmu Pengetahuan Alam) - IPA',
            'IPS' => 'IPS (Ilmu Pengetahuan Sosial)',
            'BAHASA' => 'Bahasa & Budaya',
        ];

        $programDisplay = $aliases[$programName] ?? $programName;
        $sectionDisplay = $this->formatKeyForReading($model->section_key);

        return "{$programDisplay} - {$sectionDisplay}";
    }

    /**
     * Build rich content for Program Studi with cross-references
     */
    protected function buildProgramStudiContent($model): string
    {
        $lines = [];
        $programName = strtoupper($model->program_name);

        // Header with aliases for searchability
        $lines[] = "Program Studi: {$programName}";

        // Add search aliases
        if ($programName === 'MIPA') {
            $lines[] = "Alias: IPA, Matematika, Fisika, Kimia, Biologi, Saintek";
        } elseif ($programName === 'IPS') {
            $lines[] = "Alias: Sosial, Ekonomi, Sosiologi, Geografi, Sejarah, Soshum";
        } elseif ($programName === 'BAHASA') {
            $lines[] = "Alias: Bahasa Indonesia, Bahasa Inggris, Sastra, Budaya";
        }

        $lines[] = "Section: " . $this->formatKeyForReading($model->section_key);
        $lines[] = "";

        // Convert JSON content to readable text
        if (!empty($model->content)) {
            $contentArray = is_string($model->content) ? json_decode($model->content, true) : $model->content;
            if (is_array($contentArray)) {
                $lines[] = $this->convertArrayToReadableText($contentArray);
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Build comprehensive content for Teacher model
     */
    protected function buildTeacherContent($model): string
    {
        $lines = [];
        
        // Nama guru
        $lines[] = "Nama: {$model->name}";
        
        // Tipe (Guru/Staff)
        $typeLabel = $model->type === 'staff' ? 'Staff/Tata Usaha' : 'Guru';
        $lines[] = "Jenis: {$typeLabel}";
        
        // Jabatan/Posisi
        if ($model->position) {
            $lines[] = "Jabatan: {$model->position}";
        }
        
        // Departemen/Jurusan
        if ($model->department) {
            $lines[] = "Departemen/Jurusan: {$model->department}";
        }
        
        // Status
        $status = $model->is_active ? 'Aktif' : 'Tidak Aktif';
        $lines[] = "Status: {$status}";
        
        // Tambahkan konteks tambahan untuk pencarian
        $lines[] = "";
        $lines[] = "Deskripsi:";
        $lines[] = "{$model->name} adalah {$typeLabel} di SMAN 1 Baleendah.";
        
        if ($model->position && $model->department) {
            $lines[] = "Beliau menjabat sebagai {$model->position} di departemen {$model->department}.";
        } elseif ($model->position) {
            $lines[] = "Beliau menjabat sebagai {$model->position}.";
        } elseif ($model->department) {
            $lines[] = "Beliau mengajar di departemen {$model->department}.";
        }
        
        return implode("\n", $lines);
    }

    /**
     * Build comprehensive content for Seragam model
     */
    protected function buildSeragamContent($model): string
    {
        $lines = [];
        
        $lines[] = "Seragam: {$model->name}";
        $lines[] = "Kategori: {$model->category}";
        
        if (!empty($model->usage_days)) {
            $days = is_array($model->usage_days) ? implode(', ', $model->usage_days) : $model->usage_days;
            $lines[] = "Hari Pemakaian: {$days}";
        }
        
        if ($model->description) {
            $lines[] = "";
            $lines[] = "Deskripsi:";
            $lines[] = strip_tags($model->description);
        }
        
        if ($model->rules) {
            $lines[] = "";
            $lines[] = "Aturan Berpakaian:";
            $lines[] = strip_tags($model->rules);
        }
        
        return implode("\n", $lines);
    }
}

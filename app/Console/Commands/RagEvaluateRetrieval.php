<?php

namespace App\Console\Commands;

use App\Services\RagService;
use Illuminate\Console\Command;

class RagEvaluateRetrieval extends Command
{
    protected $signature = 'rag:evaluate-retrieval {--limit=30 : Number of built-in evaluation queries to run}';
    protected $description = 'Run built-in retrieval evaluation queries and print summary';

    public function handle(RagService $ragService): int
    {
        $queries = $this->defaultQueries();
        $limit = (int) $this->option('limit');
        $queries = array_slice($queries, 0, max(1, $limit));

        $total = count($queries);
        $withContext = 0;
        $success = 0;

        $this->info("Running retrieval evaluation for {$total} queries...");

        foreach ($queries as $q) {
            $res = $ragService->generateRagResponse($q, []);
            $ok = (bool) ($res['success'] ?? false);
            $ctx = count($res['context_chunks'] ?? []) > 0 || count($res['retrieved_documents'] ?? []) > 0;

            if ($ok) {
                $success++;
            }
            if ($ctx) {
                $withContext++;
            }

            $this->line(sprintf(
                '- %s | success=%s | context=%s',
                $q,
                $ok ? 'yes' : 'no',
                $ctx ? 'yes' : 'no'
            ));
        }

        $this->newLine();
        $this->info('Summary');
        $this->line('Success rate: ' . round(($success / max(1, $total)) * 100, 2) . '%');
        $this->line('Context-hit rate: ' . round(($withContext / max(1, $total)) * 100, 2) . '%');

        return self::SUCCESS;
    }

    protected function defaultQueries(): array
    {
        return [
            'Apa saja jalur PPDB di SMAN 1 Baleendah?',
            'Berapa biaya sekolah di SMAN 1 Baleendah?',
            'Program studi apa saja yang tersedia?',
            'Apa perbedaan program MIPA dan IPS?',
            'Bagaimana informasi SPMB terbaru?',
            'Kegiatan ekstrakurikuler apa saja yang aktif?',
            'Siapa saja guru di bidang matematika?',
            'Bagaimana cara menghubungi sekolah?',
            'Alamat lengkap sekolah dimana?',
            'Apakah ada informasi kalender akademik?',
            'Apa visi misi sekolah?',
            'Bagaimana prestasi alumni?',
            'Data serapan PTN tahun terakhir bagaimana?',
            'Rata-rata nilai TKA mapel matematika berapa?',
            'Informasi kurikulum terbaru apa?',
            'Apa pengumuman terbaru di website?',
            'Apakah ada kegiatan galeri/foto terbaru?',
            'Program bahasa di sekolah seperti apa?',
            'Jadwal kegiatan ekstrakurikuler basket kapan?',
            'Siapa kepala sekolah saat ini?',
            'Kontak email sekolah apa?',
            'Info pendaftaran siswa baru tahun ini?',
            'Apa syarat masuk jalur prestasi?',
            'Apakah sekolah negeri ini gratis?',
            'Kegiatan OSIS terbaru apa?',
            'Informasi akademik semester ganjil?',
            'Program unggulan sekolah apa saja?',
            'Bagaimana fasilitas sekolah?',
            'Apa informasi FAQ terpenting untuk orang tua?',
            'Siapa guru BK di sekolah?',
        ];
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudflareStatsController extends Controller
{
    private $apiUrl = 'https://api.cloudflare.com/client/v4/graphql';

    private $apiToken;

    private $authEmail;

    private $zoneId;

    private const MAX_DATA_AGE_DAYS = 8;

    private const CACHE_TTL_MINUTES = 30; // TTL bisa lebih pendek untuk data per jam jika diinginkan

    public function __construct()
    {
        $this->apiToken = env('CLOUDFLARE_API_TOKEN');
        $this->authEmail = env('CLOUDFLARE_EMAIL');
        $this->zoneId = env('CLOUDFLARE_ZONE_ID');
    }

    // Fungsi ini sekarang lebih generik, mengambil data berdasarkan start dan end datetime
    // dan query yang diberikan. Ini akan dipakai untuk data harian dan per jam.
    private function fetchCloudflareVisits(Carbon $startDatetime, Carbon $endDatetime, string $timeGroupNode = 'httpRequestsAdaptiveGroups', string $limit = '100'): ?array
    {
        if (empty($this->apiToken) || empty($this->authEmail) || empty($this->zoneId)) {
            Log::error('Cloudflare creds not set (fetchCloudflareVisits)');

            return null;
        }

        // Pengecekan batasan umur data untuk permintaan yang lebih dari 1 hari yang lalu
        if ($startDatetime->lt(now()->subDays(self::MAX_DATA_AGE_DAYS)->startOfDay())) {
            Log::warning('Data request for period starting '.$startDatetime->toDateTimeString().' exceeds retention limit.');

            return ['visits' => 0, 'error' => 'Exceeds retention limit', 'groups' => []];
        }

        $startIso = $startDatetime->toIso8601String();
        $endIso = $endDatetime->toIso8601String(); // Untuk datetime_lt, ini harusnya eksklusif

        // Query disesuaikan untuk bisa menerima node grouping yang berbeda
        // Dan meminta 'dimensions { datetimeMinute }' atau 'dimensions { datetimeHour }' jika ada
        // Untuk httpRequestsAdaptiveGroups, kita tetap ambil 'datetime' dan proses di PHP
        $graphqlQuery = <<<GRAPHQL
        query GetAggregatedVisits(\$zoneTag: String!, \$startDatetime: String!, \$endDatetime: String!) {
          viewer {
            zones(filter: { zoneTag: \$zoneTag }) {
              # Menggunakan variabel untuk node grouping bisa jadi rumit di GraphQL sisi client
              # Jadi kita akan tetap pakai httpRequestsAdaptiveGroups dan proses di PHP
              # atau buat fungsi terpisah jika node-nya benar-benar berbeda
              httpRequestsAdaptiveGroups(
                limit: {$limit}, # Ambil cukup banyak data point
                filter: {
                  datetime_geq: \$startDatetime,
                  datetime_lt: \$endDatetime, # lt (less than) untuk eksklusif akhir
                  requestSource: "eyeball"
                }
                # orderBy: [datetime_ASC] # Dihapus karena error sebelumnya, pengurutan di PHP jika perlu
              ) {
                # Untuk httpRequestsAdaptiveGroups, kita sum semua 'visits' yang ada
                sum {
                  visits
                }
                dimensions { # Ambil datetime untuk pengelompokan/label
                    datetime # Ini adalah datetime per data point/group kecil
                }
              }
            }
          }
        }
        GRAPHQL;
        $variables = ['zoneTag' => $this->zoneId, 'startDatetime' => $startIso, 'endDatetime' => $endIso];

        try {
            $bearerToken = $this->apiToken;
            if (stripos($bearerToken, 'bearer ') !== 0) {
                $bearerToken = 'Bearer '.$bearerToken;
            }

            $response = Http::withHeaders([
                'X-Auth-Email' => $this->authEmail,
                'Authorization' => $bearerToken,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post($this->apiUrl, ['query' => $graphqlQuery, 'variables' => $variables]);

            Log::info("CF GraphQL Resp (fetchCloudflareVisits) for {$startIso} to {$endIso}: ", ['s' => $response->status(), 'b' => $response->body()]);

            if ($response->failed()) {
                Log::error("CF GraphQL Req (fetchCloudflareVisits) failed for {$startIso} to {$endIso}", ['s' => $response->status(), 'b' => $response->body()]);

                return ['visits' => 0, 'error' => 'HTTP request failed ('.$response->status().')', 'groups' => []];
            }
            $data = $response->json();
            if (isset($data['errors']) && ! empty($data['errors'])) {
                // ... (penanganan error GraphQL sama seperti sebelumnya) ...
                Log::error("CF GraphQL API (fetchCloudflareVisits) errors for {$startIso} to {$endIso}", $data['errors']);

                return ['visits' => 0, 'error' => 'GraphQL API error: '.($data['errors'][0]['message'] ?? 'Unknown'), 'groups' => []];
            }

            $adaptiveGroups = $data['data']['viewer']['zones'][0]['httpRequestsAdaptiveGroups'] ?? [];

            return ['groups' => $adaptiveGroups]; // Kembalikan semua grup untuk diproses lebih lanjut

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("CF Connection Exception (fetchCloudflareVisits) for {$startIso} to {$endIso}: ".$e->getMessage());

            return ['visits' => 0, 'error' => 'Connection timeout or network issue', 'groups' => []];
        } catch (\Exception $e) {
            Log::error("General Exception (fetchCloudflareVisits) for {$startIso} to {$endIso}: ".$e->getMessage());

            return ['visits' => 0, 'error' => 'Server exception: '.$e->getMessage(), 'groups' => []];
        }
    }

    public function getUniqueVisitors(Request $request)
    {
        try {
            $periodDays = $request->input('period', 7);
            if (! is_numeric($periodDays) || $periodDays < 1) {
                $periodDays = 1;
            }
            $effectivePeriodDays = min($periodDays, self::MAX_DATA_AGE_DAYS);

            $cacheKey = "cloudflare_total_visits_last_{$effectivePeriodDays}_days_v2"; // v2 untuk cache baru
            $ttl = now()->addMinutes(self::CACHE_TTL_MINUTES);

            $cachedData = Cache::remember($cacheKey, $ttl, function () use ($effectivePeriodDays) {
                if (empty($this->apiToken) || empty($this->authEmail) || empty($this->zoneId)) {
                    return ['error' => 'Konfigurasi API Cloudflare tidak lengkap.', 'status_code' => 500];
                }

            $grandTotalVisits = 0;
            $errorCount = 0;
            $successfulFetches = 0;
            for ($i = $effectivePeriodDays - 1; $i >= 0; $i--) {
                $currentDate = now()->subDays($i);
                $dailyResult = $this->fetchCloudflareVisits($currentDate->copy()->startOfDay(), $currentDate->copy()->endOfDay()->addSecond()); // Ambil untuk sepanjang hari

                if ($dailyResult !== null && ! isset($dailyResult['error'])) {
                    $dayVisits = 0;
                    foreach ($dailyResult['groups'] as $group) {
                        $dayVisits += (int) ($group['sum']['visits'] ?? 0);
                    }
                    $grandTotalVisits += $dayVisits;
                    $successfulFetches++;
                } else {
                    $errorCount++;
                }
                if ($effectivePeriodDays > 1 && $i > 0) {
                    usleep(300000);
                }
            }
            // ... (sisa logika pesan sama)
            $message = "Total kunjungan untuk {$successfulFetches} dari {$effectivePeriodDays} hari terakhir (maks. ".self::MAX_DATA_AGE_DAYS.' hari).';
            if ($errorCount > 0 && $successfulFetches === 0) {
                return ['error' => "Gagal mengambil data dari Cloudflare untuk semua {$effectivePeriodDays} hari.", 'status_code' => 500];
            }
            if ($errorCount > 0) {
                $message = "Data sebagian ({$successfulFetches} dari {$effectivePeriodDays} hari). {$errorCount} hari gagal. Angka adalah total kunjungan.";
            }

            return [
                'unique_visitors_total' => $grandTotalVisits,
                'message' => $message,
                'period_covered_days' => $successfulFetches,
                'total_days_requested_within_limit' => $effectivePeriodDays,
                'cached_at' => now()->toDateTimeString(),
            ];
        });
        // ... (sisa logika pengembalian response sama)
        if (isset($cachedData['error']) && ! isset($cachedData['unique_visitors_total'])) {
            return response()->json(['error' => $cachedData['error']], $cachedData['status_code'] ?? 500);
        }

        return response()->json($cachedData);
        } catch (\Exception $e) {
            Log::error('Cloudflare stats error: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal mengambil data statistik. Pastikan Cloudflare API dikonfigurasi dengan benar.', 'unique_visitors_total' => 0], 200);
        }
    }

    public function getVisitorStatsForChart(Request $request)
    {
        $periodParam = $request->input('period', '7d'); // '1d', '7d', '8d' (atau MAX_DATA_AGE_DAYS)

        $cacheKey = "cloudflare_chart_data_period_{$periodParam}_v2";
        $ttl = now()->addMinutes(self::CACHE_TTL_MINUTES);

        $cachedData = Cache::remember($cacheKey, $ttl, function () use ($periodParam) {
            if (empty($this->apiToken) || empty($this->authEmail) || empty($this->zoneId)) {
                return ['error' => 'Konfigurasi API Cloudflare tidak lengkap.', 'status_code' => 500];
            }

            $chartData = ['labels' => [], 'data' => [], 'errors_detail' => [], 'fetch_error_count' => 0, 'period_type' => 'daily'];
            $fetchErrorCount = 0;

            if ($periodParam === '1d') { // Data per jam untuk 1 hari terakhir
                $chartData['period_type'] = 'hourly';
                $today = now();
                // Ambil data untuk 24 jam terakhir, per jam
                for ($hour = 23; $hour >= 0; $hour--) { // Loop dari jam 23 ke 0 untuk urutan di chart
                    $currentHourStart = $today->copy()->subHours($hour)->startOfHour();
                    $currentHourEnd = $today->copy()->subHours($hour)->endOfHour()->addSecond(); // sampai akhir jam tersebut (eksklusif di query <)

                    // Jangan ambil data lebih tua dari MAX_DATA_AGE_DAYS (meskipun untuk 1 hari ini tidak relevan)
                    if ($currentHourStart->lt(now()->subDays(self::MAX_DATA_AGE_DAYS)->startOfDay())) {
                        $chartData['labels'][] = $currentHourStart->isoFormat('HH:[00]');
                        $chartData['data'][] = 0;
                        $chartData['errors_detail'][] = ['hour' => $currentHourStart->toDateTimeString(), 'error' => 'Exceeds retention limit'];
                        $fetchErrorCount++;

                        continue;
                    }

                    $hourlyResult = $this->fetchCloudflareVisits($currentHourStart, $currentHourEnd);
                    $chartData['labels'][] = $currentHourStart->isoFormat('HH:[00]'); // Label: 00:00, 01:00, dst.

                    if ($hourlyResult !== null && ! isset($hourlyResult['error'])) {
                        $hourVisits = 0;
                        foreach ($hourlyResult['groups'] as $group) {
                            $hourVisits += (int) ($group['sum']['visits'] ?? 0);
                        }
                        $chartData['data'][] = $hourVisits;
                    } else {
                        $chartData['data'][] = 0;
                        $fetchErrorCount++;
                        $errorMessage = $hourlyResult['error'] ?? 'Gagal ambil data per jam';
                        $chartData['errors_detail'][] = ['hour' => $currentHourStart->toDateTimeString(), 'error' => $errorMessage];
                    }
                    if ($hour > 0) {
                        usleep(100000);
                    } // Jeda kecil antar panggilan per jam
                }
                $chartData['labels'] = array_reverse($chartData['labels']); // Balik urutan label
                $chartData['data'] = array_reverse($chartData['data']);     // Balik urutan data

            } else { // Data per hari untuk 7d atau MAX_DATA_AGE_DAYS
                $chartData['period_type'] = 'daily';
                $days = ($periodParam === '7d') ? 7 : self::MAX_DATA_AGE_DAYS;
                for ($i = $days - 1; $i >= 0; $i--) {
                    $currentDate = now()->subDays($i);
                    $chartData['labels'][] = $currentDate->isoFormat('dd, DD MMM');

                    $dailyResult = $this->fetchCloudflareVisits($currentDate->copy()->startOfDay(), $currentDate->copy()->endOfDay()->addSecond());

                    if ($dailyResult !== null && ! isset($dailyResult['error'])) {
                        $dayVisits = 0;
                        foreach ($dailyResult['groups'] as $group) {
                            $dayVisits += (int) ($group['sum']['visits'] ?? 0);
                        }
                        $chartData['data'][] = $dayVisits;
                    } else {
                        $chartData['data'][] = 0;
                        $fetchErrorCount++;
                        $errorMessage = $dailyResult['error'] ?? 'Gagal ambil data harian';
                        $chartData['errors_detail'][] = ['date' => $currentDate->toDateString(), 'error' => $errorMessage];
                    }
                    if ($days > 1 && $i > 0) {
                        usleep(300000);
                    }
                }
            }

            $chartData['fetch_error_count'] = $fetchErrorCount;
            if ($fetchErrorCount === ($periodParam === '1d' ? 24 : (($periodParam === '7d') ? 7 : self::MAX_DATA_AGE_DAYS)) && $fetchErrorCount > 0) {
                return ['error' => 'Gagal mengambil semua data untuk chart.', 'status_code' => 500, 'labels' => $chartData['labels'], 'data' => $chartData['data']];
            }
            $chartData['cached_at'] = now()->toDateTimeString();

            return $chartData;
        });

        // Safe access to fetch_error_count with fallback
        $fetchErrorCount = $cachedData['fetch_error_count'] ?? 0;
        $periodCount = ($request->input('period', '7d') === '1d' ? 24 : (($request->input('period', '7d') === '7d') ? 7 : self::MAX_DATA_AGE_DAYS));
        
        if (isset($cachedData['error']) && $fetchErrorCount === $periodCount && $fetchErrorCount > 0) {
            return response()->json([
                'error' => $cachedData['error'],
                'labels' => $cachedData['labels'] ?? [],
                'data' => $cachedData['data'] ?? []
            ], $cachedData['status_code'] ?? 500);
        }

        return response()->json($cachedData);
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Monitor Cookie Security Configuration
 * 
 * This command monitors cookie security settings and alerts on violations.
 * Run periodically via cron or scheduler.
 * 
 * Usage:
 *   php artisan cookie:monitor
 *   php artisan cookie:monitor --url=https://sman1-baleendah.com
 *   php artisan cookie:monitor --alert
 */
class MonitorCookieSecurityCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cookie:monitor 
                            {--url= : URL to test (default: APP_URL)}
                            {--alert : Send alerts on violations}
                            {--slack= : Slack webhook URL for alerts}
                            {--email= : Email address for alerts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor cookie security configuration and detect violations';

    /**
     * Security violations found
     */
    protected array $violations = [];

    /**
     * Warnings found
     */
    protected array $warnings = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔒 Cookie Security Monitor');
        $this->info('═══════════════════════════════════════════');
        $this->newLine();

        $url = $this->option('url') ?: config('app.url');
        
        $this->info("Testing URL: {$url}");
        $this->newLine();

        // Test 1: Configuration Check
        $this->checkConfiguration();

        // Test 2: Live Response Check
        $this->checkLiveResponse($url);

        // Test 3: Security Headers
        $this->checkSecurityHeaders($url);

        // Display Results
        $this->displayResults();

        // Send Alerts if requested
        if ($this->option('alert') && (count($this->violations) > 0 || count($this->warnings) > 0)) {
            $this->sendAlerts();
        }

        return count($this->violations) > 0 ? 1 : 0;
    }

    /**
     * Check Laravel configuration
     */
    protected function checkConfiguration(): void
    {
        $this->info('[1/3] Checking Laravel Configuration...');

        $checks = [
            'HTTP Only' => [
                'config' => 'session.http_only',
                'expected' => true,
                'severity' => 'critical'
            ],
            'Secure Flag (Production)' => [
                'config' => 'session.secure',
                'expected' => app()->environment('production'),
                'severity' => app()->environment('production') ? 'critical' : 'warning'
            ],
            'SameSite Attribute' => [
                'config' => 'session.same_site',
                'expected' => ['lax', 'strict'],
                'severity' => 'high'
            ],
            'Session Encryption' => [
                'config' => 'session.encrypt',
                'expected' => app()->environment('production'),
                'severity' => 'medium'
            ],
        ];

        foreach ($checks as $name => $check) {
            $value = config($check['config']);
            $expected = $check['expected'];
            $severity = $check['severity'];

            if (is_array($expected)) {
                $passed = in_array($value, $expected);
            } else {
                $passed = $value === $expected;
            }

            if ($passed) {
                $this->line("  <fg=green>✓</> {$name}: {$value}");
            } else {
                $message = "{$name} - Expected: " . json_encode($expected) . ", Got: " . json_encode($value);
                
                if ($severity === 'critical' || $severity === 'high') {
                    $this->violations[] = $message;
                    $this->line("  <fg=red>✗</> {$message}");
                } else {
                    $this->warnings[] = $message;
                    $this->line("  <fg=yellow>⚠</> {$message}");
                }
            }
        }

        $this->newLine();
    }

    /**
     * Check live HTTP response
     */
    protected function checkLiveResponse(string $url): void
    {
        $this->info('[2/3] Checking Live HTTP Response...');

        try {
            $response = Http::withOptions([
                'verify' => false, // For self-signed certs in dev
                'allow_redirects' => false,
            ])->get($url);

            $cookies = $response->cookies();

            if ($cookies->count() === 0) {
                $this->warnings[] = 'No cookies found in response';
                $this->line("  <fg=yellow>⚠</> No cookies found in response");
                $this->newLine();
                return;
            }

            $this->line("  Found {$cookies->count()} cookie(s)");
            $this->newLine();

            foreach ($cookies as $cookie) {
                $this->checkCookie($cookie);
            }

        } catch (\Exception $e) {
            $this->error("  Failed to fetch URL: {$e->getMessage()}");
            $this->warnings[] = "Failed to fetch URL: {$e->getMessage()}";
        }

        $this->newLine();
    }

    /**
     * Check individual cookie
     */
    protected function checkCookie($cookieJar): void
    {
        foreach ($cookieJar->toArray() as $cookie) {
            $name = $cookie['Name'] ?? 'Unknown';
            $this->line("  Cookie: <fg=cyan>{$name}</>");

            // Check HttpOnly
            $httpOnly = $cookie['HttpOnly'] ?? false;
            if ($name !== 'XSRF-TOKEN' && !$httpOnly) {
                $violation = "Cookie '{$name}' missing HttpOnly flag";
                $this->violations[] = $violation;
                $this->line("    <fg=red>✗</> Missing HttpOnly flag");
            } else {
                $this->line("    <fg=green>✓</> HttpOnly: " . ($httpOnly ? 'Yes' : 'No (Expected for XSRF-TOKEN)'));
            }

            // Check Secure
            $secure = $cookie['Secure'] ?? false;
            if (app()->environment('production') && !$secure) {
                $violation = "Cookie '{$name}' missing Secure flag in production";
                $this->violations[] = $violation;
                $this->line("    <fg=red>✗</> Missing Secure flag (Production)");
            } else {
                $status = $secure ? 'Yes' : 'No (Development only)';
                $color = $secure ? 'green' : 'yellow';
                $this->line("    <fg={$color}>✓</> Secure: {$status}");
            }

            // Check SameSite
            $sameSite = $cookie['SameSite'] ?? null;
            if (!$sameSite) {
                $violation = "Cookie '{$name}' missing SameSite attribute";
                $this->violations[] = $violation;
                $this->line("    <fg=red>✗</> Missing SameSite attribute");
            } else {
                $this->line("    <fg=green>✓</> SameSite: {$sameSite}");
            }

            $this->newLine();
        }
    }

    /**
     * Check security headers
     */
    protected function checkSecurityHeaders(string $url): void
    {
        $this->info('[3/3] Checking Security Headers...');

        try {
            $response = Http::withOptions([
                'verify' => false,
            ])->get($url);

            $headers = [
                'Strict-Transport-Security' => 'HSTS',
                'X-Frame-Options' => 'Clickjacking Protection',
                'X-Content-Type-Options' => 'MIME Sniffing Protection',
                'X-XSS-Protection' => 'XSS Protection',
                'Content-Security-Policy' => 'CSP',
            ];

            foreach ($headers as $header => $name) {
                if ($response->hasHeader($header)) {
                    $value = $response->header($header);
                    $this->line("  <fg=green>✓</> {$name}: {$value}");
                } else {
                    $this->line("  <fg=yellow>⚠</> {$name}: Not set");
                    $this->warnings[] = "Security header '{$name}' not set";
                }
            }

        } catch (\Exception $e) {
            $this->error("  Failed to check headers: {$e->getMessage()}");
        }

        $this->newLine();
    }

    /**
     * Display summary results
     */
    protected function displayResults(): void
    {
        $this->info('═══════════════════════════════════════════');
        $this->info('📊 Summary');
        $this->info('═══════════════════════════════════════════');

        $violationCount = count($this->violations);
        $warningCount = count($this->warnings);

        if ($violationCount === 0 && $warningCount === 0) {
            $this->line("  <fg=green>✓</> All security checks passed!");
        } else {
            if ($violationCount > 0) {
                $this->line("  <fg=red>✗</> {$violationCount} violation(s) found");
            }
            if ($warningCount > 0) {
                $this->line("  <fg=yellow>⚠</> {$warningCount} warning(s) found");
            }
        }

        $this->newLine();

        // Log to file
        $this->logResults();
    }

    /**
     * Send alerts via configured channels
     */
    protected function sendAlerts(): void
    {
        $this->info('📧 Sending Alerts...');

        // Slack webhook
        if ($slackUrl = $this->option('slack')) {
            $this->sendSlackAlert($slackUrl);
        }

        // Email
        if ($email = $this->option('email')) {
            $this->sendEmailAlert($email);
        }

        $this->newLine();
    }

    /**
     * Send Slack alert
     */
    protected function sendSlackAlert(string $webhookUrl): void
    {
        $message = $this->buildAlertMessage();

        try {
            Http::post($webhookUrl, [
                'text' => '🔒 Cookie Security Alert',
                'blocks' => [
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => $message
                        ]
                    ]
                ]
            ]);

            $this->line("  <fg=green>✓</> Slack alert sent");
        } catch (\Exception $e) {
            $this->error("  Failed to send Slack alert: {$e->getMessage()}");
        }
    }

    /**
     * Send email alert
     */
    protected function sendEmailAlert(string $email): void
    {
        $message = $this->buildAlertMessage();

        try {
            Mail::raw($message, function ($mail) use ($email) {
                $mail->to($email)
                    ->subject('🔒 Cookie Security Alert - SMAN 1 Baleendah');
            });

            $this->line("  <fg=green>✓</> Email alert sent to {$email}");
        } catch (\Exception $e) {
            $this->error("  Failed to send email alert: {$e->getMessage()}");
        }
    }

    /**
     * Build alert message
     */
    protected function buildAlertMessage(): string
    {
        $message = "Cookie Security Monitor - " . now()->toDateTimeString() . "\n\n";
        
        if (count($this->violations) > 0) {
            $message .= "⛔ VIOLATIONS:\n";
            foreach ($this->violations as $violation) {
                $message .= "  • {$violation}\n";
            }
            $message .= "\n";
        }

        if (count($this->warnings) > 0) {
            $message .= "⚠️ WARNINGS:\n";
            foreach ($this->warnings as $warning) {
                $message .= "  • {$warning}\n";
            }
        }

        return $message;
    }

    /**
     * Log results to file
     */
    protected function logResults(): void
    {
        $logData = [
            'timestamp' => now()->toDateTimeString(),
            'environment' => app()->environment(),
            'violations' => $this->violations,
            'warnings' => $this->warnings,
        ];

        try {
            Log::channel('security')->info('Cookie Security Monitor', $logData);
        } catch (\Exception $e) {
            // Fallback to default channel if security channel doesn't exist
            Log::info('Cookie Security Monitor', $logData);
        }
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InstagramStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'instagram:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show Instagram scraper status and statistics';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('╔══════════════════════════════════════════════════════════════════════════════╗');
        $this->info('║              Instagram Scraper Status - SMAN 1 Baleendah                    ║');
        $this->info('╚══════════════════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Check bot accounts
        $this->showBotAccounts();
        $this->newLine();

        // Show feed statistics
        $this->showFeedStatistics();
        $this->newLine();

        // Show recent activity
        $this->showRecentActivity();
        $this->newLine();

        return Command::SUCCESS;
    }

    /**
     * Display bot account status
     */
    protected function showBotAccounts(): void
    {
        $this->info('👤 Bot Accounts:');
        
        try {
            $accounts = DB::table('sc_bot_accounts')
                ->orderBy('last_used_at', 'desc')
                ->get();

            if ($accounts->isEmpty()) {
                $this->warn('   ⚠️  No bot accounts configured');
                return;
            }

            foreach ($accounts as $account) {
                $status = $account->is_active ? '🟢 ACTIVE' : '🔴 INACTIVE';
                $lastUsed = $account->last_used_at 
                    ? \Carbon\Carbon::parse($account->last_used_at)->diffForHumans()
                    : 'Never';

                $this->line("   {$status} {$account->username}");
                $this->line("      Last used: {$lastUsed}");
                
                if ($account->notes) {
                    $this->line("      Notes: {$account->notes}");
                }
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Error: ' . $e->getMessage());
        }
    }

    /**
     * Display feed statistics
     */
    protected function showFeedStatistics(): void
    {
        $this->info('📊 Feed Statistics:');
        
        try {
            $totalFeeds = DB::table('sc_raw_news_feeds')->count();
            $processedFeeds = DB::table('sc_raw_news_feeds')->where('is_processed', true)->count();
            $pendingFeeds = DB::table('sc_raw_news_feeds')->where('is_processed', false)->count();
            $errorFeeds = DB::table('sc_raw_news_feeds')->whereNotNull('error_message')->count();

            $this->line("   Total scraped: {$totalFeeds}");
            $this->line("   ✅ Processed: {$processedFeeds}");
            $this->line("   ⏳ Pending: {$pendingFeeds}");
            
            if ($errorFeeds > 0) {
                $this->line("   ❌ Errors: {$errorFeeds}");
            }

            // Stats by source
            $this->newLine();
            $this->line('   📸 By Source:');
            
            $bySource = DB::table('sc_raw_news_feeds')
                ->select('source_username', DB::raw('COUNT(*) as count'))
                ->groupBy('source_username')
                ->orderBy('count', 'desc')
                ->get();

            foreach ($bySource as $source) {
                $this->line("      @{$source->source_username}: {$source->count} posts");
            }

        } catch (\Exception $e) {
            $this->error('   ❌ Error: ' . $e->getMessage());
        }
    }

    /**
     * Display recent activity
     */
    protected function showRecentActivity(): void
    {
        $this->info('📰 Recent Activity (Last 5):');
        
        try {
            $recentFeeds = DB::table('sc_raw_news_feeds')
                ->orderBy('scraped_at', 'desc')
                ->limit(5)
                ->get();

            if ($recentFeeds->isEmpty()) {
                $this->warn('   No activity yet');
                return;
            }

            foreach ($recentFeeds as $feed) {
                $status = $feed->is_processed ? '✅' : '⏳';
                $time = \Carbon\Carbon::parse($feed->scraped_at)->diffForHumans();
                $caption = \Illuminate\Support\Str::limit($feed->caption ?? 'No caption', 60);

                $this->line("   {$status} @{$feed->source_username} - {$time}");
                $this->line("      {$caption}");
            }

        } catch (\Exception $e) {
            $this->error('   ❌ Error: ' . $e->getMessage());
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Redirect;
use App\Models\RedirectLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RedirectAnalyticsCommand extends Command
{
    protected $signature = 'redirect:analytics
                            {--redirect= : Show analytics for a specific redirect ID}
                            {--days=30 : Number of days to analyze (default: 30)}
                            {--limit=20 : Number of results to show}
                            {--recent : Show recent requests}';

    protected $description = 'View redirect analytics';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $limit = (int) $this->option('limit');
        $redirectId = $this->option('redirect');
        $showRecent = $this->option('recent');
        
        // Validate redirect exists if ID provided
        if ($redirectId) {
            $redirect = Redirect::find($redirectId);
            if (!$redirect) {
                $this->error("Redirect #{$redirectId} not found");
                $this->newLine();
                $this->line('Use "php artisan redirect:list" to see available redirects');
                return self::FAILURE;
            }
        }

        $startDate = now()->subDays($days);

        // Show recent requests
        if ($showRecent) {
            $this->showRecentRequests($limit, $redirectId);
            return self::SUCCESS;
        }

        // Show summary
        $this->showSummary($startDate, $redirectId);

        // Show top redirects
        if (!$redirectId) {
            $this->newLine();
            $this->showTopRedirects($startDate, $limit);
        }

        // Show details for specific redirect
        if ($redirectId) {
            $this->newLine();
            $this->showRedirectDetails($redirectId, $startDate);
        }

        return self::SUCCESS;
    }

    protected function showSummary($startDate, $redirectId = null): void
    {
        $query = RedirectLog::where('created_at', '>=', $startDate);
        
        if ($redirectId) {
            $query->where('redirect_id', $redirectId);
        }

        $totalRequests = $query->count();
        $uniqueDomains = $query->distinct('request_domain')->count('request_domain');
        $uniqueIps = $query->distinct('ip_address')->count('ip_address');

        $this->components->info('Analytics Summary');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Requests', number_format($totalRequests)],
                ['Unique Domains', number_format($uniqueDomains)],
                ['Unique IPs', number_format($uniqueIps)],
                ['Period', $startDate->format('Y-m-d') . ' to ' . now()->format('Y-m-d')],
            ]
        );
    }

    protected function showTopRedirects($startDate, $limit): void
    {
        $topRedirects = RedirectLog::select('redirect_id', DB::raw('count(*) as total'))
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('redirect_id')
            ->groupBy('redirect_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        if ($topRedirects->isEmpty()) {
            $this->components->warn('No redirect data found');
            $this->newLine();
            $this->line('No requests have been logged yet.');
            $this->line('  • Check active redirects: php artisan redirect:list --active');
            $this->line('  • Test a redirect by visiting a source URL');
            return;
        }

        $this->components->info('Top Redirects');
        
        $rows = [];
        foreach ($topRedirects as $log) {
            $redirect = Redirect::find($log->redirect_id);
            if (!$redirect) {
                continue;
            }

            $source = $redirect->source_type === 'domain' 
                ? $redirect->source_domain . ' (domain)' 
                : $redirect->source_path;

            $rows[] = [
                $redirect->id,
                $source,
                substr($redirect->destination, 0, 40) . (strlen($redirect->destination) > 40 ? '...' : ''),
                number_format($log->total),
            ];
        }

        $this->table(['ID', 'Source', 'Destination', 'Hits'], $rows);
    }

    protected function showRedirectDetails($redirectId, $startDate): void
    {
        $redirect = Redirect::find($redirectId);
        
        if (!$redirect) {
            $this->error("Redirect #{$redirectId} not found");
            return;
        }

        $source = $redirect->source_type === 'domain' 
            ? $redirect->source_domain 
            : $redirect->source_path;

        $this->components->info("Details for Redirect #{$redirectId}");
        $this->line("Source: {$source}");
        $this->line("Destination: {$redirect->destination}");
        $this->line("Status: " . ($redirect->is_active ? 'Active' : 'Inactive'));
        $this->newLine();

        // Top referrers
        $topReferrers = RedirectLog::select('referer', DB::raw('count(*) as total'))
            ->where('redirect_id', $redirectId)
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('referer')
            ->groupBy('referer')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        if ($topReferrers->isNotEmpty()) {
            $this->line('Top Referrers:');
            $rows = $topReferrers->map(fn($r) => [
                substr($r->referer, 0, 60) . (strlen($r->referer) > 60 ? '...' : ''),
                number_format($r->total),
            ]);
            $this->table(['Referrer', 'Count'], $rows);
        }

        // Daily breakdown
        $this->newLine();
        $dailyStats = RedirectLog::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as total')
            )
            ->where('redirect_id', $redirectId)
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderByDesc('date')
            ->limit(14)
            ->get();

        if ($dailyStats->isNotEmpty()) {
            $this->line('Daily Requests (Last 14 Days):');
            $rows = $dailyStats->map(fn($s) => [$s->date, number_format($s->total)]);
            $this->table(['Date', 'Requests'], $rows);
        }
    }

    protected function showRecentRequests($limit, $redirectId = null): void
    {
        $query = RedirectLog::with('redirect')
            ->orderByDesc('created_at')
            ->limit($limit);

        if ($redirectId) {
            $query->where('redirect_id', $redirectId);
        }

        $logs = $query->get();

        if ($logs->isEmpty()) {
            $this->components->warn('No recent requests found');
            $this->newLine();
            $this->line('No requests have been logged yet.');
            $this->line('  • Check active redirects: php artisan redirect:list --active');
            $this->line('  • Test a redirect by visiting a source URL');
            return;
        }

        $this->components->info('Recent Requests');
        
        $rows = [];
        foreach ($logs as $log) {
            $redirectSource = 'N/A';
            if ($log->redirect) {
                $redirectSource = $log->redirect->source_type === 'domain'
                    ? $log->redirect->source_domain
                    : $log->redirect->source_path;
            }

            $rows[] = [
                $log->created_at->format('Y-m-d H:i:s'),
                substr($log->request_url, 0, 40) . (strlen($log->request_url) > 40 ? '...' : ''),
                substr($log->destination_url, 0, 40) . (strlen($log->destination_url) > 40 ? '...' : ''),
                $log->status_code,
                $log->ip_address ?? 'N/A',
            ];
        }

        $this->table(['Time', 'From', 'To', 'Status', 'IP'], $rows);
    }
}

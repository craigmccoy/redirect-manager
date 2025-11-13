<?php

namespace App\Console\Commands;

use App\Models\Redirect;
use Illuminate\Console\Command;

class RedirectListCommand extends Command
{
    protected $signature = 'redirect:list
                            {--active : Show only active redirects}
                            {--inactive : Show only inactive redirects}
                            {--type= : Filter by type (url or domain)}';

    protected $description = 'List all redirect rules';

    public function handle(): int
    {
        $query = Redirect::query()->byPriority()->orderBy('id');

        if ($this->option('active')) {
            $query->active();
        } elseif ($this->option('inactive')) {
            $query->where('is_active', false);
        }

        if ($type = $this->option('type')) {
            if (!in_array($type, ['url', 'domain'])) {
                $this->error('Type must be either "url" or "domain"');
                return self::FAILURE;
            }
            $query->where('source_type', $type);
        }

        $redirects = $query->get();

        if ($redirects->isEmpty()) {
            $this->components->warn('No redirects found');
            return self::SUCCESS;
        }

        $headers = ['ID', 'Type', 'Source', 'Destination', 'Opts', 'Status', 'Priority', 'Schedule', 'Active', 'Logs'];
        $rows = [];

        foreach ($redirects as $redirect) {
            $source = $redirect->source_type === 'domain' 
                ? $redirect->source_domain . ' (domain)' 
                : $redirect->source_path;

            // Build options string
            $opts = '';
            if ($redirect->preserve_path) $opts .= 'P';
            if ($redirect->force_https) $opts .= 'H';
            if ($redirect->case_sensitive) $opts .= 'C';
            if ($redirect->trailing_slash_mode === 'add') $opts .= '/+';
            if ($redirect->trailing_slash_mode === 'remove') $opts .= '/-';

            // Schedule info
            $schedule = '';
            if ($redirect->active_from || $redirect->active_until) {
                if ($redirect->active_from && $redirect->active_until) {
                    $schedule = 'Scheduled';
                } elseif ($redirect->active_from) {
                    $schedule = 'From: ' . $redirect->active_from->format('m/d');
                } elseif ($redirect->active_until) {
                    $schedule = 'Until: ' . $redirect->active_until->format('m/d');
                }
            }

            $rows[] = [
                $redirect->id,
                $redirect->source_type,
                $source,
                strlen($redirect->destination) > 30 
                    ? substr($redirect->destination, 0, 27) . '...' 
                    : $redirect->destination,
                $opts ?: '-',
                $redirect->status_code,
                $redirect->priority,
                $schedule ?: '-',
                $redirect->is_active ? '✓' : '✗',
                $redirect->logs()->count(),
            ];
        }

        $this->table($headers, $rows);
        $this->line("\nTotal: " . $redirects->count() . ' redirect(s)');
        $this->newLine();
        $this->line('Options: P=Preserve Path, H=Force HTTPS, C=Case Sensitive, /+=Add Slash, /-=Remove Slash');

        return self::SUCCESS;
    }
}

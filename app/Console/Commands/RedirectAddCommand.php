<?php

namespace App\Console\Commands;

use App\Models\Redirect;
use Illuminate\Console\Command;

class RedirectAddCommand extends Command
{
    protected $signature = 'redirect:add
                            {--type=url : Type of redirect (url or domain)}
                            {--domain= : Source domain for domain-wide redirects}
                            {--path= : Source path for URL-specific redirects}
                            {--destination= : Destination URL}
                            {--preserve-path : Preserve the original path in the redirect}
                            {--no-preserve-query : Do not preserve query strings (preserves by default)}
                            {--force-https : Force HTTPS in destination URL}
                            {--case-sensitive : Enable case-sensitive matching}
                            {--trailing-slash= : Trailing slash mode (add, remove, or ignore)}
                            {--from= : Start date/time for scheduled redirect (Y-m-d H:i:s)}
                            {--until= : End date/time for scheduled redirect (Y-m-d H:i:s)}
                            {--status=301 : HTTP status code (301, 302, 307, 308)}
                            {--priority=0 : Priority (higher = checked first)}
                            {--notes= : Optional notes}';

    protected $description = 'Add a new redirect rule';

    public function handle(): int
    {
        $this->components->info('Create a New Redirect');
        $this->newLine();

        // Get redirect type
        $type = $this->option('type');
        if (!$type || !in_array($type, ['url', 'domain'])) {
            $type = $this->choice(
                'What type of redirect?',
                ['domain' => 'Domain-wide (redirect entire domain)', 'url' => 'URL-specific (redirect specific path)'],
                'url'
            );
        }

        // Get source based on type
        $domain = null;
        $path = null;
        
        if ($type === 'domain') {
            $domain = $this->option('domain') ?: $this->ask('Source domain (e.g., oldsite.com or *.oldsite.com)');
            if (!$domain) {
                $this->error('Domain is required');
                return self::FAILURE;
            }
        } else {
            $path = $this->option('path') ?: $this->ask('Source path (e.g., /old-page or /blog/*)');
            if (!$path) {
                $this->error('Path is required');
                return self::FAILURE;
            }
        }

        // Get destination
        $destination = $this->option('destination') ?: $this->ask('Destination URL (e.g., https://newsite.com/page)');
        if (!$destination) {
            $this->error('Destination is required');
            return self::FAILURE;
        }

        // Interactive options or use provided values
        $this->newLine();
        $configureAdvanced = $this->confirm('Configure advanced options?', false);
        
        // Basic options
        $preservePath = $this->option('preserve-path') !== null 
            ? $this->option('preserve-path')
            : ($configureAdvanced && $this->confirm('Preserve the original path in redirect?', $type === 'domain'));
            
        $preserveQuery = $this->option('no-preserve-query') !== null
            ? !$this->option('no-preserve-query')
            : (!$configureAdvanced || $this->confirm('Preserve query strings?', true));
        
        $forceHttps = $this->option('force-https') !== null
            ? $this->option('force-https')
            : ($configureAdvanced && $this->confirm('Force HTTPS on destination?', false));
        
        $caseSensitive = $this->option('case-sensitive') !== null
            ? $this->option('case-sensitive')
            : ($configureAdvanced && $this->confirm('Enable case-sensitive matching?', false));
        
        // Trailing slash
        $trailingSlashMode = $this->option('trailing-slash');
        if (!$trailingSlashMode && $configureAdvanced) {
            $trailingSlashChoice = $this->choice(
                'Trailing slash handling?',
                ['ignore' => 'Leave as-is', 'add' => 'Always add trailing slash', 'remove' => 'Always remove trailing slash'],
                'ignore'
            );
            $trailingSlashMode = $trailingSlashChoice === 'ignore' ? null : $trailingSlashChoice;
        }
        
        if ($trailingSlashMode && !in_array($trailingSlashMode, ['add', 'remove'])) {
            $this->error('Trailing slash mode must be "add" or "remove"');
            return self::FAILURE;
        }

        // Status code
        $statusCode = $this->option('status');
        if (!$statusCode || !in_array((int)$statusCode, [301, 302, 307, 308])) {
            if ($configureAdvanced) {
                $statusCode = (int) $this->choice(
                    'HTTP status code?',
                    [301 => '301 - Permanent redirect', 302 => '302 - Temporary redirect', 307 => '307 - Temporary (preserve method)', 308 => '308 - Permanent (preserve method)'],
                    301
                );
            } else {
                $statusCode = 301;
            }
        }
        $statusCode = (int) $statusCode;

        // Priority
        $priority = $this->option('priority') !== null
            ? (int) $this->option('priority')
            : ($configureAdvanced ? (int) $this->ask('Priority (higher = checked first)', '0') : 0);

        // Schedule
        $activeFrom = null;
        $activeUntil = null;

        if ($this->option('from') || ($configureAdvanced && $this->confirm('Schedule this redirect?', false))) {
            $fromInput = $this->option('from') ?: $this->ask('Start date/time (Y-m-d H:i:s) or leave empty for immediate');
            if ($fromInput) {
                try {
                    $activeFrom = \Carbon\Carbon::parse($fromInput);
                } catch (\Exception $e) {
                    $this->error('Invalid date format. Use: Y-m-d H:i:s');
                    return self::FAILURE;
                }
            }

            $untilInput = $this->option('until') ?: $this->ask('End date/time (Y-m-d H:i:s) or leave empty for no expiration');
            if ($untilInput) {
                try {
                    $activeUntil = \Carbon\Carbon::parse($untilInput);
                } catch (\Exception $e) {
                    $this->error('Invalid date format. Use: Y-m-d H:i:s');
                    return self::FAILURE;
                }
            }

            // Validate date range
            if ($activeFrom && $activeUntil && $activeFrom->isAfter($activeUntil)) {
                $this->error('Start date must be before end date');
                return self::FAILURE;
            }
        }

        // Notes
        $notes = $this->option('notes') ?: ($configureAdvanced ? $this->ask('Notes (optional)') : null);

        // Show summary and confirm
        $this->newLine();
        $this->components->info('Redirect Summary');
        $this->table(
            ['Setting', 'Value'],
            [
                ['Type', ucfirst($type)],
                ['Source', $type === 'domain' ? $domain : $path],
                ['Destination', $destination],
                ['Preserve Path', $preservePath ? 'Yes' : 'No'],
                ['Preserve Query', $preserveQuery ? 'Yes' : 'No'],
                ['Force HTTPS', $forceHttps ? 'Yes' : 'No'],
                ['Case Sensitive', $caseSensitive ? 'Yes' : 'No'],
                ['Trailing Slash', $trailingSlashMode ? ucfirst($trailingSlashMode) : 'Ignore'],
                ['Status Code', $statusCode],
                ['Priority', $priority],
                ['Active From', $activeFrom ? $activeFrom->format('Y-m-d H:i:s') : 'Immediate'],
                ['Active Until', $activeUntil ? $activeUntil->format('Y-m-d H:i:s') : 'No expiration'],
                ['Notes', $notes ?: '-'],
            ]
        );
        
        if (!$this->confirm('Create this redirect?', true)) {
            $this->components->warn('Redirect creation cancelled');
            return self::SUCCESS;
        }

        $redirect = Redirect::create([
            'source_type' => $type,
            'source_domain' => $domain,
            'source_path' => $path,
            'destination' => $destination,
            'preserve_path' => $preservePath,
            'preserve_query_string' => $preserveQuery,
            'force_https' => $forceHttps,
            'case_sensitive' => $caseSensitive,
            'trailing_slash_mode' => $trailingSlashMode,
            'status_code' => $statusCode,
            'priority' => $priority,
            'notes' => $notes,
            'is_active' => true,
            'active_from' => $activeFrom,
            'active_until' => $activeUntil,
        ]);

        $this->newLine();
        $this->components->success("✓ Redirect created successfully!");
        $this->line("  ID: {$redirect->id}");
        $this->newLine();
        
        $this->components->info('Next steps:');
        $this->line("  • View: php artisan redirect:show {$redirect->id}");
        $this->line("  • List all: php artisan redirect:list");
        $this->line("  • Test the redirect by visiting the source URL");

        return self::SUCCESS;
    }
}

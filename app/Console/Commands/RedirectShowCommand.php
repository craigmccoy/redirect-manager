<?php

namespace App\Console\Commands;

use App\Models\Redirect;
use Illuminate\Console\Command;

class RedirectShowCommand extends Command
{
    protected $signature = 'redirect:show {id : The ID of the redirect to view}';

    protected $description = 'Show detailed information about a redirect';

    public function handle(): int
    {
        $redirect = Redirect::with('logs')->find($this->argument('id'));

        if (!$redirect) {
            $this->error("Redirect #{$this->argument('id')} not found");
            return self::FAILURE;
        }

        $this->components->info("Redirect #{$redirect->id}");
        $this->newLine();

        // Basic Information
        $this->components->twoColumnDetail('<fg=gray>Type</>', ucfirst($redirect->source_type));
        
        if ($redirect->source_type === 'domain') {
            $this->components->twoColumnDetail('<fg=gray>Source Domain</>', $redirect->source_domain);
        } else {
            $this->components->twoColumnDetail('<fg=gray>Source Path</>', $redirect->source_path);
        }
        
        $this->components->twoColumnDetail('<fg=gray>Destination</>', $redirect->destination);
        $this->components->twoColumnDetail('<fg=gray>Status Code</>', (string) $redirect->status_code);
        $this->components->twoColumnDetail('<fg=gray>Priority</>', (string) $redirect->priority);
        $this->components->twoColumnDetail('<fg=gray>Active</>', $redirect->is_active ? '✓ Yes' : '✗ No');

        // Schedule
        if ($redirect->active_from || $redirect->active_until) {
            $this->newLine();
            $this->line('<fg=yellow>Schedule</>');
            if ($redirect->active_from) {
                $this->components->twoColumnDetail('<fg=gray>Active From</>', $redirect->active_from->format('Y-m-d H:i:s'));
            }
            if ($redirect->active_until) {
                $this->components->twoColumnDetail('<fg=gray>Active Until</>', $redirect->active_until->format('Y-m-d H:i:s'));
            }
            
            $isScheduledActive = $redirect->isCurrentlyActive();
            $this->components->twoColumnDetail(
                '<fg=gray>Currently Active</>',
                $isScheduledActive ? '<fg=green>✓ Yes</>' : '<fg=red>✗ No</>'
            );
        }

        // Options
        $this->newLine();
        $this->line('<fg=yellow>Options</>');
        $this->components->twoColumnDetail('<fg=gray>Preserve Path</>', $redirect->preserve_path ? '✓ Yes' : '✗ No');
        $this->components->twoColumnDetail('<fg=gray>Preserve Query String</>', $redirect->preserve_query_string ? '✓ Yes' : '✗ No');
        $this->components->twoColumnDetail('<fg=gray>Force HTTPS</>', $redirect->force_https ? '✓ Yes' : '✗ No');
        $this->components->twoColumnDetail('<fg=gray>Case Sensitive</>', $redirect->case_sensitive ? '✓ Yes' : '✗ No');
        $this->components->twoColumnDetail('<fg=gray>Trailing Slash</>', $redirect->trailing_slash_mode ? ucfirst($redirect->trailing_slash_mode) : 'Ignore');

        // Notes
        if ($redirect->notes) {
            $this->newLine();
            $this->line('<fg=yellow>Notes</>');
            $this->line('  ' . $redirect->notes);
        }

        // Analytics
        $logCount = $redirect->logs->count();
        $this->newLine();
        $this->line('<fg=yellow>Analytics</>');
        $this->components->twoColumnDetail('<fg=gray>Total Requests</>', number_format($logCount));
        
        if ($logCount > 0) {
            $firstLog = $redirect->logs()->orderBy('created_at')->first();
            $lastLog = $redirect->logs()->orderByDesc('created_at')->first();
            
            $this->components->twoColumnDetail('<fg=gray>First Request</>', $firstLog->created_at->format('Y-m-d H:i:s'));
            $this->components->twoColumnDetail('<fg=gray>Latest Request</>', $lastLog->created_at->format('Y-m-d H:i:s'));
        }

        // Metadata
        $this->newLine();
        $this->line('<fg=gray>Created:</> ' . $redirect->created_at->format('Y-m-d H:i:s'));
        $this->line('<fg=gray>Updated:</> ' . $redirect->updated_at->format('Y-m-d H:i:s'));

        // Actions
        $this->newLine();
        $this->components->info('Available Actions:');
        $this->line("  • Update: php artisan redirect:update {$redirect->id}");
        $this->line("  • Toggle: php artisan redirect:toggle {$redirect->id}");
        $this->line("  • Delete: php artisan redirect:delete {$redirect->id}");
        $this->line("  • Analytics: php artisan redirect:analytics --redirect={$redirect->id}");

        return self::SUCCESS;
    }
}

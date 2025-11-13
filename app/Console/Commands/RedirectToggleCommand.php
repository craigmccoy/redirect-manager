<?php

namespace App\Console\Commands;

use App\Models\Redirect;
use Illuminate\Console\Command;

class RedirectToggleCommand extends Command
{
    protected $signature = 'redirect:toggle {id : The ID of the redirect to toggle}';

    protected $description = 'Toggle a redirect active/inactive status';

    public function handle(): int
    {
        $redirect = Redirect::find($this->argument('id'));

        if (!$redirect) {
            $this->error("Redirect #{$this->argument('id')} not found");
            return self::FAILURE;
        }

        $source = $redirect->source_type === 'domain' 
            ? $redirect->source_domain 
            : $redirect->source_path;

        $currentStatus = $redirect->is_active ? 'active' : 'inactive';
        $newStatus = $redirect->is_active ? 'inactive' : 'active';

        $this->components->info("Toggle Redirect #{$redirect->id}");
        $this->newLine();
        $this->line("  Source: {$source}");
        $this->line("  Current status: <fg=".($redirect->is_active ? 'green' : 'red').">{$currentStatus}</>");
        $this->line("  New status: <fg=".(!$redirect->is_active ? 'green' : 'red').">{$newStatus}</>");
        $this->newLine();
        
        // Show impact
        if ($redirect->is_active) {
            $this->components->warn('⚠ Disabling will stop processing requests immediately');
        } else {
            $this->components->info('✓ Enabling will start processing requests');
        }
        $this->newLine();

        if (!$this->confirm("Toggle this redirect to {$newStatus}?", true)) {
            $this->components->info('Toggle cancelled');
            return self::SUCCESS;
        }

        $redirect->is_active = !$redirect->is_active;
        $redirect->save();

        $this->newLine();
        $icon = $redirect->is_active ? '✓' : '✗';
        $color = $redirect->is_active ? 'green' : 'yellow';
        $this->components->success("{$icon} Redirect #{$redirect->id} is now <fg={$color}>{$newStatus}</>");
        
        if (!$redirect->is_active) {
            $this->line("  The redirect will not process any requests until re-enabled");
        } else {
            $this->line("  The redirect is now processing requests");
        }
        
        $this->newLine();
        $this->components->info('Next steps:');
        $this->line("  • View details: php artisan redirect:show {$redirect->id}");
        $this->line("  • List all: php artisan redirect:list");

        return self::SUCCESS;
    }
}

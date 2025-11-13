<?php

namespace App\Console\Commands;

use App\Models\Redirect;
use Illuminate\Console\Command;

class RedirectDeleteCommand extends Command
{
    protected $signature = 'redirect:delete {id : The ID of the redirect to delete}';

    protected $description = 'Delete a redirect rule';

    public function handle(): int
    {
        $redirect = Redirect::find($this->argument('id'));

        if (!$redirect) {
            $this->error("Redirect #{$this->argument('id')} not found");
            return self::FAILURE;
        }

        // Show redirect details
        $this->components->warn("Delete Redirect #{$redirect->id}");
        $this->newLine();

        $source = $redirect->source_type === 'domain' 
            ? $redirect->source_domain 
            : $redirect->source_path;

        $this->line("  Source: <fg=red>{$source}</>");
        $this->line("  Destination: <fg=red>{$redirect->destination}</>");
        $this->line("  Type: " . ucfirst($redirect->source_type));
        $this->line("  Status: " . ($redirect->is_active ? 'Active' : 'Inactive'));
        
        $logCount = $redirect->logs()->count();
        $this->line("  Analytics: {$logCount} request(s) logged");

        if ($logCount > 0) {
            $this->newLine();
            $this->components->warn("⚠ This redirect has {$logCount} logged request(s)");
            $this->line("  Analytics data will also be deleted");
        }

        $this->newLine();
        
        if (!$this->confirm('Are you sure you want to delete this redirect?', false)) {
            $this->components->info('Deletion cancelled');
            return self::SUCCESS;
        }

        // Double confirmation for redirects with logs
        if ($logCount > 0) {
            if (!$this->confirm('This will permanently delete all analytics. Continue?', false)) {
                $this->components->info('Deletion cancelled');
                return self::SUCCESS;
            }
        }

        $redirect->delete();

        $this->newLine();
        $this->components->success('✓ Redirect deleted successfully');

        return self::SUCCESS;
    }
}

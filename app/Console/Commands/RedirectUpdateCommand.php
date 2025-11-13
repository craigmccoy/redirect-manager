<?php

namespace App\Console\Commands;

use App\Models\Redirect;
use Illuminate\Console\Command;

class RedirectUpdateCommand extends Command
{
    protected $signature = 'redirect:update 
                            {id : The ID of the redirect to update}
                            {--domain= : Update source domain}
                            {--path= : Update source path}
                            {--destination= : Update destination URL}
                            {--preserve-path : Enable path preservation}
                            {--no-preserve-path : Disable path preservation}
                            {--preserve-query : Enable query string preservation}
                            {--no-preserve-query : Disable query string preservation}
                            {--force-https : Enable HTTPS enforcement}
                            {--no-force-https : Disable HTTPS enforcement}
                            {--case-sensitive : Enable case-sensitive matching}
                            {--no-case-sensitive : Disable case-sensitive matching}
                            {--trailing-slash= : Set trailing slash mode (add, remove, or empty to clear)}
                            {--from= : Set start date/time (Y-m-d H:i:s or empty to clear)}
                            {--until= : Set end date/time (Y-m-d H:i:s or empty to clear)}
                            {--status= : Update HTTP status code}
                            {--priority= : Update priority}
                            {--notes= : Update notes}';

    protected $description = 'Update an existing redirect rule';

    public function handle(): int
    {
        $redirect = Redirect::find($this->argument('id'));

        if (!$redirect) {
            $this->error("Redirect #{$this->argument('id')} not found");
            return self::FAILURE;
        }

        // Show current redirect details
        $this->components->info("Update Redirect #{$redirect->id}");
        $this->newLine();
        
        $source = $redirect->source_type === 'domain' ? $redirect->source_domain : $redirect->source_path;
        $this->line("Current: <fg=cyan>{$source}</> → <fg=cyan>{$redirect->destination}</>");
        $this->newLine();

        // If no options provided, go interactive
        $hasOptions = $this->option('domain') || $this->option('path') || $this->option('destination') ||
                      $this->option('status') || $this->option('priority') !== null || 
                      $this->option('notes') !== null ||
                      $this->option('preserve-path') || $this->option('no-preserve-path') ||
                      $this->option('force-https') || $this->option('no-force-https') ||
                      $this->option('case-sensitive') || $this->option('no-case-sensitive') ||
                      $this->option('trailing-slash') !== null ||
                      $this->option('from') !== null || $this->option('until') !== null;

        if (!$hasOptions) {
            return $this->interactiveUpdate($redirect);
        }

        $updated = false;

        if ($domain = $this->option('domain')) {
            $redirect->source_domain = $domain;
            $redirect->source_type = 'domain';
            $redirect->source_path = null;
            $updated = true;
        }

        if ($path = $this->option('path')) {
            $redirect->source_path = $path;
            $redirect->source_type = 'url';
            $redirect->source_domain = null;
            $updated = true;
        }

        if ($destination = $this->option('destination')) {
            $redirect->destination = $destination;
            $updated = true;
        }

        if ($status = $this->option('status')) {
            $statusCode = (int) $status;
            if (!in_array($statusCode, [301, 302, 307, 308])) {
                $this->error('Status code must be 301, 302, 307, or 308');
                return self::FAILURE;
            }
            $redirect->status_code = $statusCode;
            $updated = true;
        }

        if ($this->option('priority') !== null) {
            $redirect->priority = (int) $this->option('priority');
            $updated = true;
        }

        if ($this->option('notes') !== null) {
            $redirect->notes = $this->option('notes');
            $updated = true;
        }

        if ($this->option('preserve-path')) {
            $redirect->preserve_path = true;
            $updated = true;
        }

        if ($this->option('no-preserve-path')) {
            $redirect->preserve_path = false;
            $updated = true;
        }

        if ($this->option('preserve-query')) {
            $redirect->preserve_query_string = true;
            $updated = true;
        }

        if ($this->option('no-preserve-query')) {
            $redirect->preserve_query_string = false;
            $updated = true;
        }

        if ($this->option('force-https')) {
            $redirect->force_https = true;
            $updated = true;
        }

        if ($this->option('no-force-https')) {
            $redirect->force_https = false;
            $updated = true;
        }

        if ($this->option('case-sensitive')) {
            $redirect->case_sensitive = true;
            $updated = true;
        }

        if ($this->option('no-case-sensitive')) {
            $redirect->case_sensitive = false;
            $updated = true;
        }

        if ($this->option('trailing-slash') !== null) {
            $trailingSlash = $this->option('trailing-slash');
            if ($trailingSlash === '') {
                $redirect->trailing_slash_mode = null;
            } elseif (in_array($trailingSlash, ['add', 'remove'])) {
                $redirect->trailing_slash_mode = $trailingSlash;
            } else {
                $this->error('Trailing slash mode must be "add", "remove", or empty to clear');
                return self::FAILURE;
            }
            $updated = true;
        }

        if ($this->option('from') !== null) {
            $from = $this->option('from');
            if ($from === '') {
                $redirect->active_from = null;
            } else {
                try {
                    $redirect->active_from = \Carbon\Carbon::parse($from);
                } catch (\Exception $e) {
                    $this->error('Invalid --from date format. Use: Y-m-d H:i:s');
                    return self::FAILURE;
                }
            }
            $updated = true;
        }

        if ($this->option('until') !== null) {
            $until = $this->option('until');
            if ($until === '') {
                $redirect->active_until = null;
            } else {
                try {
                    $redirect->active_until = \Carbon\Carbon::parse($until);
                } catch (\Exception $e) {
                    $this->error('Invalid --until date format. Use: Y-m-d H:i:s');
                    return self::FAILURE;
                }
            }
            $updated = true;
        }

        if (!$updated) {
            $this->components->warn('No updates specified');
            return self::SUCCESS;
        }

        // Validate date range if both are set
        if ($redirect->active_from && $redirect->active_until && $redirect->active_from->isAfter($redirect->active_until)) {
            $this->error('active_from date must be before active_until date');
            return self::FAILURE;
        }

        $redirect->save();

        $this->components->success("✓ Redirect #{$redirect->id} updated successfully");

        return self::SUCCESS;
    }

    protected function interactiveUpdate(Redirect $redirect): int
    {
        $choices = [
            'destination' => 'Change destination URL',
            'options' => 'Update options (path preservation, HTTPS, etc.)',
            'schedule' => 'Update schedule',
            'priority' => 'Change priority',
            'notes' => 'Update notes',
            'done' => 'Save changes',
        ];

        $updated = false;

        while (true) {
            $this->newLine();
            $choice = $this->choice('What would you like to update?', $choices, 'done');

            if ($choice === 'done') {
                break;
            }

            switch ($choice) {
                case 'destination':
                    $newDestination = $this->ask('New destination URL', $redirect->destination);
                    if ($newDestination !== $redirect->destination) {
                        $redirect->destination = $newDestination;
                        $updated = true;
                        $this->components->info('✓ Destination updated');
                    }
                    break;

                case 'options':
                    if ($this->confirm('Preserve path?', $redirect->preserve_path)) {
                        $redirect->preserve_path = true;
                        $updated = true;
                    } else {
                        $redirect->preserve_path = false;
                        $updated = true;
                    }

                    if ($this->confirm('Preserve query strings?', $redirect->preserve_query_string)) {
                        $redirect->preserve_query_string = true;
                        $updated = true;
                    } else {
                        $redirect->preserve_query_string = false;
                        $updated = true;
                    }

                    if ($this->confirm('Force HTTPS?', $redirect->force_https)) {
                        $redirect->force_https = true;
                        $updated = true;
                    } else {
                        $redirect->force_https = false;
                        $updated = true;
                    }

                    if ($this->confirm('Case-sensitive matching?', $redirect->case_sensitive)) {
                        $redirect->case_sensitive = true;
                        $updated = true;
                    } else {
                        $redirect->case_sensitive = false;
                        $updated = true;
                    }

                    $trailingSlash = $this->choice(
                        'Trailing slash handling?',
                        ['ignore' => 'Leave as-is', 'add' => 'Always add', 'remove' => 'Always remove'],
                        $redirect->trailing_slash_mode ?: 'ignore'
                    );
                    $redirect->trailing_slash_mode = $trailingSlash === 'ignore' ? null : $trailingSlash;
                    $updated = true;

                    $statusCode = $this->choice(
                        'HTTP status code?',
                        [301 => '301 - Permanent', 302 => '302 - Temporary', 307 => '307 - Temp (preserve method)', 308 => '308 - Perm (preserve method)'],
                        $redirect->status_code
                    );
                    $redirect->status_code = (int) $statusCode;
                    $updated = true;

                    $this->components->info('✓ Options updated');
                    break;

                case 'schedule':
                    $fromInput = $this->ask(
                        'Start date/time (Y-m-d H:i:s) or leave empty to clear',
                        $redirect->active_from ? $redirect->active_from->format('Y-m-d H:i:s') : ''
                    );
                    
                    if ($fromInput === '') {
                        $redirect->active_from = null;
                    } else {
                        try {
                            $redirect->active_from = \Carbon\Carbon::parse($fromInput);
                        } catch (\Exception $e) {
                            $this->error('Invalid date format');
                            continue 2;
                        }
                    }

                    $untilInput = $this->ask(
                        'End date/time (Y-m-d H:i:s) or leave empty to clear',
                        $redirect->active_until ? $redirect->active_until->format('Y-m-d H:i:s') : ''
                    );
                    
                    if ($untilInput === '') {
                        $redirect->active_until = null;
                    } else {
                        try {
                            $redirect->active_until = \Carbon\Carbon::parse($untilInput);
                        } catch (\Exception $e) {
                            $this->error('Invalid date format');
                            continue 2;
                        }
                    }

                    $updated = true;
                    $this->components->info('✓ Schedule updated');
                    break;

                case 'priority':
                    $newPriority = (int) $this->ask('Priority (higher = checked first)', (string) $redirect->priority);
                    if ($newPriority !== $redirect->priority) {
                        $redirect->priority = $newPriority;
                        $updated = true;
                        $this->components->info('✓ Priority updated');
                    }
                    break;

                case 'notes':
                    $newNotes = $this->ask('Notes', $redirect->notes);
                    if ($newNotes !== $redirect->notes) {
                        $redirect->notes = $newNotes;
                        $updated = true;
                        $this->components->info('✓ Notes updated');
                    }
                    break;
            }
        }

        if (!$updated) {
            $this->components->warn('No changes made');
            return self::SUCCESS;
        }

        if ($this->confirm('Save changes?', true)) {
            $redirect->save();
            $this->components->success("✓ Redirect #{$redirect->id} updated successfully");
        } else {
            $this->components->warn('Changes discarded');
        }

        return self::SUCCESS;
    }
}

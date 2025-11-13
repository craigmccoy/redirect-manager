<?php

namespace Database\Seeders;

use App\Models\Redirect;
use Illuminate\Database\Seeder;

class RedirectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding redirects...');

        // 1. Simple URL redirect
        Redirect::create([
            'source_type' => 'url',
            'source_path' => '/old-about',
            'destination' => 'https://example.com/about-us',
            'status_code' => 301,
            'priority' => 0,
            'notes' => 'Simple URL redirect example',
            'is_active' => true,
        ]);

        // 2. Domain-wide redirect with path preservation
        Redirect::create([
            'source_type' => 'domain',
            'source_domain' => 'oldsite.local',
            'destination' => 'https://newsite.com',
            'preserve_path' => true,
            'preserve_query_string' => true,
            'status_code' => 301,
            'priority' => 10,
            'notes' => 'Full site migration with path preservation',
            'is_active' => true,
        ]);

        // 3. Wildcard subdomain redirect
        Redirect::create([
            'source_type' => 'domain',
            'source_domain' => '*.oldsite.local',
            'destination' => 'https://newsite.com',
            'preserve_path' => true,
            'status_code' => 301,
            'priority' => 5,
            'notes' => 'Catch all subdomains',
            'is_active' => true,
        ]);

        // 4. Wildcard path redirect
        Redirect::create([
            'source_type' => 'url',
            'source_path' => '/blog/*',
            'destination' => 'https://blog.example.com',
            'preserve_path' => true,
            'status_code' => 301,
            'priority' => 15,
            'notes' => 'Redirect all blog posts to new blog domain',
            'is_active' => true,
        ]);

        // 5. HTTP to HTTPS enforcement
        Redirect::create([
            'source_type' => 'domain',
            'source_domain' => 'secure.local',
            'destination' => 'http://secure.example.com',
            'preserve_path' => true,
            'force_https' => true,
            'status_code' => 301,
            'priority' => 20,
            'notes' => 'SSL migration - force HTTPS',
            'is_active' => true,
        ]);

        // 6. Case-sensitive redirect
        Redirect::create([
            'source_type' => 'url',
            'source_path' => '/PrivacyPolicy',
            'destination' => 'https://example.com/legal/privacy',
            'case_sensitive' => true,
            'status_code' => 301,
            'priority' => 25,
            'notes' => 'Exact case match required',
            'is_active' => true,
        ]);

        // 7. Trailing slash normalization (add)
        Redirect::create([
            'source_type' => 'domain',
            'source_domain' => 'seo.local',
            'destination' => 'https://example.com',
            'preserve_path' => true,
            'trailing_slash_mode' => 'add',
            'status_code' => 301,
            'priority' => 8,
            'notes' => 'SEO - ensure trailing slashes',
            'is_active' => true,
        ]);

        // 8. Scheduled redirect (active)
        Redirect::create([
            'source_type' => 'url',
            'source_path' => '/summer-sale',
            'destination' => 'https://example.com/promotions/summer2024',
            'status_code' => 302,
            'priority' => 30,
            'notes' => 'Summer sale campaign',
            'is_active' => true,
            'active_from' => now()->subDays(7),
            'active_until' => now()->addDays(23),
        ]);

        // 9. Scheduled redirect (future)
        Redirect::create([
            'source_type' => 'url',
            'source_path' => '/black-friday',
            'destination' => 'https://example.com/sales/black-friday-2024',
            'status_code' => 302,
            'priority' => 30,
            'notes' => 'Black Friday sale - starts in future',
            'is_active' => true,
            'active_from' => now()->addDays(30),
            'active_until' => now()->addDays(34),
        ]);

        // 10. Scheduled redirect (expired)
        Redirect::create([
            'source_type' => 'url',
            'source_path' => '/spring-sale',
            'destination' => 'https://example.com/promotions/spring2024',
            'status_code' => 302,
            'priority' => 30,
            'notes' => 'Spring sale - already ended',
            'is_active' => true,
            'active_from' => now()->subDays(60),
            'active_until' => now()->subDays(30),
        ]);

        // 11. Landing page redirect (no path preservation)
        Redirect::create([
            'source_type' => 'domain',
            'source_domain' => 'campaign.local',
            'destination' => 'https://example.com/welcome',
            'preserve_path' => false,
            'status_code' => 302,
            'priority' => 12,
            'notes' => 'Campaign - redirect all to landing page',
            'is_active' => true,
        ]);

        // 12. Combined advanced features
        Redirect::create([
            'source_type' => 'domain',
            'source_domain' => 'legacy.local',
            'destination' => 'http://modern.example.com',
            'preserve_path' => true,
            'preserve_query_string' => true,
            'force_https' => true,
            'trailing_slash_mode' => 'add',
            'status_code' => 301,
            'priority' => 50,
            'notes' => 'Full migration with all features enabled',
            'is_active' => true,
        ]);

        // 13. Inactive redirect (for testing toggle)
        Redirect::create([
            'source_type' => 'url',
            'source_path' => '/inactive-test',
            'destination' => 'https://example.com/inactive',
            'status_code' => 301,
            'priority' => 0,
            'notes' => 'Disabled redirect for testing',
            'is_active' => false,
        ]);

        // 14. High priority specific override
        Redirect::create([
            'source_type' => 'url',
            'source_path' => '/blog/special-post',
            'destination' => 'https://special.example.com/featured',
            'status_code' => 302,
            'priority' => 100,
            'notes' => 'High priority override for wildcard /blog/*',
            'is_active' => true,
        ]);

        // 15. Query string removal example
        Redirect::create([
            'source_type' => 'url',
            'source_path' => '/clean-url',
            'destination' => 'https://example.com/page',
            'preserve_query_string' => false,
            'status_code' => 301,
            'priority' => 5,
            'notes' => 'Strips query strings for clean URLs',
            'is_active' => true,
        ]);

        $this->command->info('✓ Created 15 sample redirects');
        $this->command->newLine();
        
        $this->command->table(
            ['Feature', 'Count'],
            [
                ['Active redirects', Redirect::where('is_active', true)->count()],
                ['Domain redirects', Redirect::where('source_type', 'domain')->count()],
                ['URL redirects', Redirect::where('source_type', 'url')->count()],
                ['Scheduled redirects', Redirect::whereNotNull('active_from')->orWhereNotNull('active_until')->count()],
                ['With path preservation', Redirect::where('preserve_path', true)->count()],
                ['With HTTPS enforcement', Redirect::where('force_https', true)->count()],
            ]
        );
    }
}

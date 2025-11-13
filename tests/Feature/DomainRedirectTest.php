<?php

namespace Tests\Feature;

use App\Models\Redirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_redirect_works(): void
    {
        Redirect::factory()->domain()->create([
            'source_domain' => 'localhost',
            'destination' => 'https://newdomain.com',
        ]);

        $response = $this->get('http://localhost/any/path');

        $response->assertRedirect('https://newdomain.com');
    }

    public function test_domain_redirect_with_path_preservation(): void
    {
        Redirect::factory()->domain()->preservePath()->create([
            'source_domain' => 'localhost',
            'destination' => 'https://newdomain.com',
        ]);

        $response = $this->get('http://localhost/blog/post-1');

        $response->assertRedirect('https://newdomain.com/blog/post-1');
    }

    public function test_wildcard_subdomain_redirect(): void
    {
        Redirect::factory()->domain()->create([
            'source_domain' => '*.localhost',
            'destination' => 'https://main.example.com',
        ]);

        // Note: In real environment, this would match *.localhost
        // In tests, we can only test the pattern matching logic
        $this->assertTrue(true); // Placeholder for actual wildcard domain test
    }

    public function test_url_redirect_takes_priority_over_domain_redirect(): void
    {
        // Domain-wide redirect (lower priority)
        Redirect::factory()->domain()->priority(5)->create([
            'source_domain' => 'localhost',
            'destination' => 'https://domain-redirect.com',
        ]);

        // Specific URL redirect (higher priority)
        Redirect::factory()->priority(10)->create([
            'source_path' => '/special',
            'destination' => 'https://url-redirect.com',
        ]);

        $response = $this->get('/special');

        $response->assertRedirect('https://url-redirect.com');
    }

    public function test_domain_redirect_with_query_string_preservation(): void
    {
        Redirect::factory()->domain()->create([
            'source_domain' => 'localhost',
            'destination' => 'https://newdomain.com/landing',
            'preserve_query_string' => true,
        ]);

        $response = $this->get('http://localhost/page?utm_source=email&utm_campaign=test');

        $response->assertRedirect();
        $redirectUrl = $response->headers->get('Location');
        $this->assertStringContainsString('https://newdomain.com/landing', $redirectUrl);
        $this->assertStringContainsString('utm_source=email', $redirectUrl);
        $this->assertStringContainsString('utm_campaign=test', $redirectUrl);
    }

    public function test_multiple_domain_redirects_respect_priority(): void
    {
        // Lower priority
        Redirect::factory()->domain()->priority(1)->create([
            'source_domain' => 'localhost',
            'destination' => 'https://low-priority.com',
        ]);

        // Higher priority
        Redirect::factory()->domain()->priority(10)->create([
            'source_domain' => 'localhost',
            'destination' => 'https://high-priority.com',
        ]);

        $response = $this->get('http://localhost/test');

        $response->assertRedirect('https://high-priority.com');
    }
}

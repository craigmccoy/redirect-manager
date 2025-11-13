<?php

namespace Tests\Feature;

use App\Models\Redirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatusPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_page_loads_successfully(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('status');
    }

    public function test_status_page_displays_correct_statistics(): void
    {
        // Create test data
        Redirect::factory()->count(5)->create();
        Redirect::factory()->inactive()->count(2)->create();
        Redirect::factory()->domain()->count(3)->create();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewHas('stats');
        
        $stats = $response->viewData('stats');
        
        $this->assertEquals(10, $stats['total_redirects']);
        $this->assertEquals(8, $stats['active_redirects']); // 5 + 3 (domain)
        $this->assertEquals(3, $stats['domain_redirects']);
        $this->assertEquals(7, $stats['url_redirects']); // 5 + 2 (inactive)
    }

    public function test_status_page_shows_zero_stats_when_empty(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        
        $stats = $response->viewData('stats');
        
        $this->assertEquals(0, $stats['total_redirects']);
        $this->assertEquals(0, $stats['active_redirects']);
        $this->assertEquals(0, $stats['domain_redirects']);
        $this->assertEquals(0, $stats['url_redirects']);
    }

    public function test_status_page_route_is_protected(): void
    {
        // The status page route is defined before fallback, 
        // so it takes precedence over wildcard redirects
        Redirect::factory()->create([
            'source_path' => '/other-page',
            'destination' => 'https://example.com',
        ]);

        $response = $this->get('/');

        // Status page should always be accessible
        $response->assertStatus(200);
        $response->assertViewIs('status');
    }

    public function test_status_page_contains_key_elements(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Redirect Manager');
        $response->assertSee('Total Redirects');
        $response->assertSee('Active Redirects');
        $response->assertSee('Domain Redirects');
        $response->assertSee('URL Redirects');
        $response->assertSee('Health Check');
    }
}

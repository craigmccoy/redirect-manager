<?php

namespace Tests\Feature;

use App\Models\Redirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_check_endpoint_is_accessible(): void
    {
        $response = $this->get('/up');

        $response->assertStatus(200);
    }

    public function test_health_check_not_affected_by_redirects(): void
    {
        // Create a redirect that would match /up if middleware processed it
        Redirect::factory()->create([
            'source_path' => '/up',
            'destination' => 'https://example.com/health',
        ]);

        $response = $this->get('/up');

        // Should still return 200, not redirect
        $response->assertStatus(200);
        $response->assertDontSee('example.com');
    }

    public function test_health_check_with_wildcard_redirect(): void
    {
        // Create a wildcard redirect
        Redirect::factory()->create([
            'source_path' => '/*',
            'destination' => 'https://example.com',
        ]);

        $response = $this->get('/up');

        // Health check should still work
        $response->assertStatus(200);
    }

    public function test_health_check_response_format(): void
    {
        $response = $this->get('/up');

        $response->assertStatus(200);
        // Laravel's health check returns a simple response
        // Just verify it returns 200 OK - content doesn't matter
        $this->assertNotNull($response->getContent());
    }
}

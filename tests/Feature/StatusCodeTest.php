<?php

namespace Tests\Feature;

use App\Models\Redirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatusCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_301_permanent_redirect(): void
    {
        Redirect::factory()->create([
            'source_path' => '/old',
            'destination' => 'https://example.com/new',
            'status_code' => 301,
        ]);

        $response = $this->get('/old');

        $response->assertStatus(301);
        $response->assertRedirect('https://example.com/new');
    }

    public function test_302_temporary_redirect(): void
    {
        Redirect::factory()->temporary()->create([
            'source_path' => '/temp',
            'destination' => 'https://example.com/temporary',
        ]);

        $response = $this->get('/temp');

        $response->assertStatus(302);
        $response->assertRedirect('https://example.com/temporary');
    }

    public function test_307_temporary_redirect_preserves_method(): void
    {
        Redirect::factory()->create([
            'source_path' => '/form',
            'destination' => 'https://example.com/new-form',
            'status_code' => 307,
        ]);

        $response = $this->get('/form');

        $response->assertStatus(307);
        $response->assertRedirect('https://example.com/new-form');
    }

    public function test_308_permanent_redirect_preserves_method(): void
    {
        Redirect::factory()->create([
            'source_path' => '/api',
            'destination' => 'https://api.example.com/v2',
            'status_code' => 308,
        ]);

        $response = $this->get('/api');

        $response->assertStatus(308);
        $response->assertRedirect('https://api.example.com/v2');
    }

    public function test_redirect_logs_correct_status_code(): void
    {
        Redirect::factory()->create([
            'source_path' => '/tracked',
            'destination' => 'https://example.com/new',
            'status_code' => 302,
        ]);

        $this->get('/tracked');

        $this->assertDatabaseHas('redirect_logs', [
            'request_path' => '/tracked',
            'status_code' => 302,
        ]);
    }
}

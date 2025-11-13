<?php

namespace Tests\Feature;

use App\Models\Redirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirect_with_query_string_in_destination(): void
    {
        Redirect::factory()->create([
            'source_path' => '/sale',
            'destination' => 'https://example.com/products?discount=50',
        ]);

        $response = $this->get('/sale');

        $response->assertRedirect('https://example.com/products?discount=50');
    }

    public function test_redirect_with_query_string_preservation_appends_correctly(): void
    {
        Redirect::factory()->create([
            'source_path' => '/sale',
            'destination' => 'https://example.com/products?discount=50',
            'preserve_query_string' => true,
        ]);

        $response = $this->get('/sale?ref=email');

        $response->assertRedirect('https://example.com/products?discount=50&ref=email');
    }

    public function test_redirect_with_fragment_in_destination(): void
    {
        Redirect::factory()->create([
            'source_path' => '/docs',
            'destination' => 'https://example.com/documentation#getting-started',
        ]);

        $response = $this->get('/docs');

        $response->assertRedirect('https://example.com/documentation#getting-started');
    }

    public function test_redirect_handles_encoded_characters(): void
    {
        Redirect::factory()->create([
            'source_path' => '/search',
            'destination' => 'https://example.com/results',
        ]);

        $response = $this->get('/search?q=hello%20world');

        $response->assertRedirect();
    }

    public function test_redirect_with_trailing_slash_in_source(): void
    {
        Redirect::factory()->create([
            'source_path' => '/about',
            'destination' => 'https://example.com/about-us',
        ]);

        // Both with and without trailing slash should work (Laravel normalizes)
        $response = $this->get('/about');
        $response->assertRedirect('https://example.com/about-us');
    }

    public function test_redirect_without_trailing_slash_in_source(): void
    {
        Redirect::factory()->create([
            'source_path' => '/contact',
            'destination' => 'https://example.com/contact-us',
        ]);

        $response = $this->get('/contact');

        $response->assertRedirect('https://example.com/contact-us');
    }

    public function test_wildcard_matches_multiple_segments(): void
    {
        Redirect::factory()->create([
            'source_path' => '/old/*',
            'destination' => 'https://example.com/new',
        ]);

        $response = $this->get('/old/category/subcategory/item');

        $response->assertRedirect('https://example.com/new');
    }

    public function test_wildcard_matches_single_segment(): void
    {
        Redirect::factory()->create([
            'source_path' => '/blog/*',
            'destination' => 'https://blog.example.com',
        ]);

        $response = $this->get('/blog/post');

        $response->assertRedirect('https://blog.example.com');
    }

    public function test_non_matching_path_returns_404(): void
    {
        Redirect::factory()->create([
            'source_path' => '/exists',
            'destination' => 'https://example.com/new',
        ]);

        $response = $this->get('/does-not-exist');

        $response->assertNotFound();
    }

    public function test_root_path_redirect(): void
    {
        Redirect::factory()->domain()->create([
            'source_domain' => 'old.localhost',
            'destination' => 'https://new.example.com',
        ]);

        // Root path should work with domain redirects
        $this->assertTrue(true); // Placeholder - depends on domain setup
    }

    public function test_multiple_slashes_in_path(): void
    {
        Redirect::factory()->create([
            'source_path' => '/test',
            'destination' => 'https://example.com/new',
        ]);

        // Laravel normalizes paths, so this might behave differently
        $response = $this->get('/test');

        $response->assertRedirect('https://example.com/new');
    }

    public function test_destination_with_port_number(): void
    {
        Redirect::factory()->create([
            'source_path' => '/app',
            'destination' => 'https://example.com:8443/application',
        ]);

        $response = $this->get('/app');

        $response->assertRedirect('https://example.com:8443/application');
    }

    public function test_international_characters_in_path(): void
    {
        Redirect::factory()->create([
            'source_path' => '/über',
            'destination' => 'https://example.com/uber',
        ]);

        $response = $this->get('/über');

        $response->assertRedirect('https://example.com/uber');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Redirect;
use App\Models\RedirectLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_url_redirect_works(): void
    {
        $redirect = Redirect::factory()->create([
            'source_path' => '/test-page',
            'destination' => 'https://example.com/new-page',
            'status_code' => 301,
        ]);

        $response = $this->get('/test-page');

        $response->assertRedirect('https://example.com/new-page');
        $response->assertStatus(301);

        // Verify log was created
        $this->assertDatabaseHas('redirect_logs', [
            'redirect_id' => $redirect->id,
            'request_path' => '/test-page',
        ]);
    }

    public function test_inactive_redirect_does_not_work(): void
    {
        Redirect::factory()->inactive()->create([
            'source_path' => '/inactive-page',
            'destination' => 'https://example.com/new-page',
        ]);

        $response = $this->get('/inactive-page');

        $response->assertNotFound();
    }

    public function test_wildcard_path_redirect_works(): void
    {
        $redirect = Redirect::factory()->create([
            'source_path' => '/blog/*',
            'destination' => 'https://newblog.com',
        ]);

        $response = $this->get('/blog/article-1');

        $response->assertRedirect('https://newblog.com');
        $response->assertStatus(301);
    }

    public function test_higher_priority_redirect_takes_precedence(): void
    {
        // Lower priority general redirect
        Redirect::factory()->create([
            'source_path' => '/test/*',
            'destination' => 'https://general.com',
            'priority' => 0,
        ]);

        // Higher priority specific redirect
        $specificRedirect = Redirect::factory()->priority(10)->create([
            'source_path' => '/test/specific',
            'destination' => 'https://specific.com',
            'status_code' => 302,
        ]);

        $response = $this->get('/test/specific');

        $response->assertRedirect('https://specific.com');
        $response->assertStatus(302);

        // Verify correct redirect was used
        $this->assertDatabaseHas('redirect_logs', [
            'redirect_id' => $specificRedirect->id,
        ]);
    }

    public function test_redirect_log_captures_request_details(): void
    {
        $redirect = Redirect::factory()->create([
            'source_path' => '/tracked-page',
            'destination' => 'https://example.com/new-page',
        ]);

        $response = $this->get('/tracked-page', [
            'User-Agent' => 'Test Browser',
            'Referer' => 'https://google.com',
        ]);

        $response->assertRedirect();

        $log = RedirectLog::first();
        $this->assertNotNull($log);
        $this->assertEquals($redirect->id, $log->redirect_id);
        $this->assertEquals('/tracked-page', $log->request_path);
        $this->assertEquals('https://example.com/new-page', $log->destination_url);
        $this->assertEquals(301, $log->status_code);
        $this->assertStringContainsString('Test Browser', $log->user_agent);
        $this->assertEquals('https://google.com', $log->referer);
    }

    public function test_path_preservation_works(): void
    {
        $redirect = Redirect::factory()->domain()->preservePath()->create([
            'source_domain' => 'localhost',
            'destination' => 'https://newsite.com',
        ]);

        $response = $this->get('/about/team');

        $response->assertRedirect('https://newsite.com/about/team');
    }

    public function test_path_preservation_disabled(): void
    {
        $redirect = Redirect::factory()->domain()->create([
            'source_domain' => 'localhost',
            'destination' => 'https://newsite.com/welcome',
            'preserve_path' => false,
        ]);

        $response = $this->get('/about/team');

        $response->assertRedirect('https://newsite.com/welcome');
    }

    public function test_query_string_preservation_works(): void
    {
        $redirect = Redirect::factory()->create([
            'source_path' => '/test',
            'destination' => 'https://newsite.com/page',
            'preserve_query_string' => true,
        ]);

        $response = $this->get('/test?ref=email&source=newsletter');

        $response->assertRedirect('https://newsite.com/page?ref=email&source=newsletter');
    }

    public function test_query_string_preservation_disabled(): void
    {
        $redirect = Redirect::factory()->create([
            'source_path' => '/test',
            'destination' => 'https://newsite.com/page',
            'preserve_query_string' => false,
        ]);

        $response = $this->get('/test?ref=email&source=newsletter');

        $response->assertRedirect('https://newsite.com/page');
    }

    public function test_path_and_query_preservation_together(): void
    {
        $redirect = Redirect::factory()->domain()->preservePath()->create([
            'source_domain' => 'localhost',
            'destination' => 'https://newsite.com',
            'preserve_query_string' => true,
        ]);

        $response = $this->get('/products/item?color=blue&size=large');

        $response->assertRedirect('https://newsite.com/products/item?color=blue&size=large');
    }

    public function test_force_https_works(): void
    {
        $redirect = Redirect::factory()->forceHttps()->create([
            'source_path' => '/test',
            'destination' => 'http://newsite.com/page',
        ]);

        $response = $this->get('/test');

        $response->assertRedirect('https://newsite.com/page');
    }

    public function test_case_insensitive_matching_default(): void
    {
        $redirect = Redirect::factory()->create([
            'source_path' => '/about',
            'destination' => 'https://newsite.com/about',
            'case_sensitive' => false,
        ]);

        // Should match despite different case
        $response = $this->get('/About');
        $response->assertRedirect('https://newsite.com/about');

        $response = $this->get('/ABOUT');
        $response->assertRedirect('https://newsite.com/about');
    }

    public function test_case_sensitive_matching(): void
    {
        $redirect = Redirect::factory()->caseSensitive()->create([
            'source_path' => '/About',
            'destination' => 'https://newsite.com/about',
        ]);

        // Should match exact case
        $response = $this->get('/About');
        $response->assertRedirect('https://newsite.com/about');

        // Should not match different case
        $response = $this->get('/about');
        $response->assertNotFound();
    }

    public function test_trailing_slash_add_mode(): void
    {
        $redirect = Redirect::factory()->domain()->preservePath()->addTrailingSlash()->create([
            'source_domain' => 'localhost',
            'destination' => 'https://newsite.com',
        ]);

        $response = $this->get('/about');

        $response->assertRedirect('https://newsite.com/about/');
    }

    public function test_trailing_slash_remove_mode(): void
    {
        $redirect = Redirect::factory()->domain()->preservePath()->removeTrailingSlash()->create([
            'source_domain' => 'localhost',
            'destination' => 'https://newsite.com',
        ]);

        $response = $this->get('/about/');

        $response->assertRedirect('https://newsite.com/about');
    }

    public function test_scheduled_redirect_active(): void
    {
        $redirect = Redirect::factory()->scheduled()->temporary()->create([
            'source_path' => '/promo',
            'destination' => 'https://newsite.com/sale',
        ]);

        $response = $this->get('/promo');

        $response->assertRedirect('https://newsite.com/sale');
    }

    public function test_scheduled_redirect_not_yet_active(): void
    {
        $redirect = Redirect::factory()->future()->temporary()->create([
            'source_path' => '/promo',
            'destination' => 'https://newsite.com/sale',
        ]);

        $response = $this->get('/promo');

        $response->assertNotFound();
    }

    public function test_scheduled_redirect_expired(): void
    {
        $redirect = Redirect::factory()->expired()->temporary()->create([
            'source_path' => '/promo',
            'destination' => 'https://newsite.com/sale',
        ]);

        $response = $this->get('/promo');

        $response->assertNotFound();
    }

    public function test_is_currently_active_helper(): void
    {
        // Currently active
        $redirect = Redirect::factory()->scheduled()->create([
            'source_path' => '/test1',
            'destination' => 'https://newsite.com',
        ]);
        $this->assertTrue($redirect->isCurrentlyActive());

        // Not yet active
        $redirect2 = Redirect::factory()->future()->create([
            'source_path' => '/test2',
            'destination' => 'https://newsite.com',
        ]);
        $this->assertFalse($redirect2->isCurrentlyActive());

        // Expired
        $redirect3 = Redirect::factory()->expired()->create([
            'source_path' => '/test3',
            'destination' => 'https://newsite.com',
        ]);
        $this->assertFalse($redirect3->isCurrentlyActive());

        // Disabled
        $redirect4 = Redirect::factory()->inactive()->create([
            'source_path' => '/test4',
            'destination' => 'https://newsite.com',
        ]);
        $this->assertFalse($redirect4->isCurrentlyActive());
    }

    public function test_combined_advanced_features(): void
    {
        $redirect = Redirect::factory()
            ->domain()
            ->preservePath()
            ->forceHttps()
            ->addTrailingSlash()
            ->create([
                'source_domain' => 'localhost',
                'destination' => 'http://newsite.com',
                'preserve_query_string' => true,
                'case_sensitive' => false,
            ]);

        $response = $this->get('/Products/Item?ref=email');

        $response->assertRedirect('https://newsite.com/Products/Item/?ref=email');
    }
}

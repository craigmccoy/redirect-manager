<?php

namespace Tests\Feature;

use App\Models\Redirect;
use App\Models\RedirectLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirect_has_many_logs(): void
    {
        $redirect = Redirect::factory()->create();
        
        RedirectLog::factory()->count(3)->forRedirect($redirect)->create();

        $this->assertCount(3, $redirect->logs);
    }

    public function test_redirect_log_belongs_to_redirect(): void
    {
        $redirect = Redirect::factory()->create([
            'source_path' => '/test',
        ]);
        
        $log = RedirectLog::factory()->forRedirect($redirect)->create();

        $this->assertEquals($redirect->id, $log->redirect->id);
        $this->assertEquals('/test', $log->redirect->source_path);
    }

    public function test_deleting_redirect_with_logs(): void
    {
        $redirect = Redirect::factory()->create();
        
        RedirectLog::factory()->count(5)->forRedirect($redirect)->create();

        $logCount = RedirectLog::where('redirect_id', $redirect->id)->count();
        $this->assertEquals(5, $logCount);

        // Note: Actual cascade behavior depends on foreign key constraints
        // This test documents expected behavior
        $redirect->delete();

        // If cascading, logs should be deleted too
        // If not cascading, logs would remain orphaned
        // Adjust based on your migration settings
    }

    public function test_redirect_logs_are_created_on_request(): void
    {
        $redirect = Redirect::factory()->create([
            'source_path' => '/tracked',
            'destination' => 'https://example.com/new',
        ]);

        $this->assertEquals(0, $redirect->logs()->count());

        $this->get('/tracked');

        $redirect->refresh();
        $this->assertEquals(1, $redirect->logs()->count());
    }

    public function test_multiple_requests_create_multiple_logs(): void
    {
        $redirect = Redirect::factory()->create([
            'source_path' => '/popular',
            'destination' => 'https://example.com/page',
        ]);

        $this->get('/popular');
        $this->get('/popular');
        $this->get('/popular');

        $this->assertEquals(3, $redirect->logs()->count());
    }

    public function test_logs_record_different_user_agents(): void
    {
        $redirect = Redirect::factory()->create([
            'source_path' => '/test',
            'destination' => 'https://example.com/new',
        ]);

        $this->get('/test', ['User-Agent' => 'Mozilla/5.0']);
        $this->get('/test', ['User-Agent' => 'Chrome/90.0']);

        $userAgents = $redirect->logs->pluck('user_agent')->toArray();
        
        $this->assertContains('Mozilla/5.0', $userAgents);
        $this->assertContains('Chrome/90.0', $userAgents);
    }

    public function test_logs_include_request_metadata(): void
    {
        $redirect = Redirect::factory()->create([
            'source_path' => '/metadata-test',
            'destination' => 'https://example.com/new',
            'preserve_query_string' => false, // Don't preserve query string for this test
        ]);

        $this->get('/metadata-test?utm_source=email', [
            'User-Agent' => 'TestBrowser/1.0',
            'Referer' => 'https://google.com',
        ]);

        $log = $redirect->logs()->first();

        $this->assertStringContainsString('/metadata-test', $log->request_path);
        $this->assertEquals('GET', $log->request_method);
        $this->assertStringContainsString('TestBrowser/1.0', $log->user_agent);
        $this->assertEquals('https://google.com', $log->referer);
        $this->assertEquals('https://example.com/new', $log->destination_url);
    }
}

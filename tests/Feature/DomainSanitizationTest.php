<?php

namespace Tests\Feature;

use App\Console\Commands\RedirectAddCommand;
use App\Console\Commands\RedirectUpdateCommand;
use App\Models\Redirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainSanitizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sanitize_domain_removes_https_protocol(): void
    {
        $command = new RedirectAddCommand();
        $method = new \ReflectionMethod($command, 'sanitizeDomain');
        
        $result = $method->invoke($command, 'https://oldsite.com');
        
        $this->assertEquals('oldsite.com', $result);
    }

    public function test_sanitize_domain_removes_http_protocol(): void
    {
        $command = new RedirectAddCommand();
        $method = new \ReflectionMethod($command, 'sanitizeDomain');
        
        $result = $method->invoke($command, 'http://oldsite.com');
        
        $this->assertEquals('oldsite.com', $result);
    }

    public function test_sanitize_domain_removes_trailing_slash(): void
    {
        $command = new RedirectAddCommand();
        $method = new \ReflectionMethod($command, 'sanitizeDomain');
        
        $result = $method->invoke($command, 'oldsite.com/');
        
        $this->assertEquals('oldsite.com', $result);
    }

    public function test_sanitize_domain_removes_protocol_and_path(): void
    {
        $command = new RedirectAddCommand();
        $method = new \ReflectionMethod($command, 'sanitizeDomain');
        
        $result = $method->invoke($command, 'https://oldsite.com/some/path');
        
        $this->assertEquals('oldsite.com', $result);
    }

    public function test_sanitize_domain_preserves_wildcard(): void
    {
        $command = new RedirectAddCommand();
        $method = new \ReflectionMethod($command, 'sanitizeDomain');
        
        $result = $method->invoke($command, 'https://*.oldsite.com');
        
        $this->assertEquals('*.oldsite.com', $result);
    }
    
    public function test_sanitize_domain_handles_complex_case(): void
    {
        $command = new RedirectAddCommand();
        $method = new \ReflectionMethod($command, 'sanitizeDomain');
        
        $result = $method->invoke($command, 'HTTPS://SubDomain.OldSite.COM/path/to/page/');
        
        $this->assertEquals('SubDomain.OldSite.COM', $result);
    }
    
    public function test_update_command_has_sanitize_domain(): void
    {
        $command = new RedirectUpdateCommand();
        $method = new \ReflectionMethod($command, 'sanitizeDomain');
        
        $result = $method->invoke($command, 'https://newdomain.com/');
        
        $this->assertEquals('newdomain.com', $result);
    }

    public function test_sanitized_domain_matches_correctly(): void
    {
        // Create redirect with protocol (will be sanitized)
        $redirect = Redirect::factory()->create([
            'source_type' => 'domain',
            'source_domain' => 'oldsite.com', // Already sanitized in factory
            'destination' => 'https://newsite.com',
        ]);

        // Test that request to oldsite.com matches
        $response = $this->get('http://oldsite.com/test-page');

        $response->assertRedirect('https://newsite.com');
        $response->assertStatus(301);
    }

    public function test_update_command_sanitizes_domain(): void
    {
        $redirect = Redirect::factory()->create([
            'source_type' => 'url',
            'source_path' => '/test',
            'destination' => 'https://example.com',
        ]);

        $this->artisan('redirect:update', [
            'id' => $redirect->id,
            '--domain' => 'https://newdomain.com/',
        ])->assertSuccessful();

        $redirect->refresh();
        
        // Should sanitize domain
        $this->assertEquals('domain', $redirect->source_type);
        $this->assertEquals('newdomain.com', $redirect->source_domain);
        $this->assertNull($redirect->source_path);
    }
}

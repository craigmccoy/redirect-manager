<?php

namespace Tests\Feature;

use App\Models\Redirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectScopesTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_scope_filters_inactive_redirects(): void
    {
        Redirect::factory()->create(['is_active' => true]);
        Redirect::factory()->inactive()->create();
        Redirect::factory()->create(['is_active' => true]);

        $activeCount = Redirect::active()->count();

        $this->assertEquals(2, $activeCount);
    }

    public function test_active_scope_respects_schedule(): void
    {
        // Currently active
        Redirect::factory()->scheduled()->create();
        
        // Future
        Redirect::factory()->future()->create();
        
        // Expired
        Redirect::factory()->expired()->create();

        $activeCount = Redirect::active()->count();

        $this->assertEquals(1, $activeCount);
    }

    public function test_domain_type_scope(): void
    {
        Redirect::factory()->domain()->count(3)->create();
        Redirect::factory()->count(2)->create(); // URL type

        $domainCount = Redirect::domainType()->count();

        $this->assertEquals(3, $domainCount);
    }

    public function test_url_type_scope(): void
    {
        Redirect::factory()->domain()->count(2)->create();
        Redirect::factory()->count(3)->create(); // URL type

        $urlCount = Redirect::urlType()->count();

        $this->assertEquals(3, $urlCount);
    }

    public function test_by_priority_scope_orders_correctly(): void
    {
        $low = Redirect::factory()->priority(1)->create();
        $high = Redirect::factory()->priority(100)->create();
        $medium = Redirect::factory()->priority(50)->create();

        $redirects = Redirect::byPriority()->get();

        $this->assertEquals($high->id, $redirects[0]->id);
        $this->assertEquals($medium->id, $redirects[1]->id);
        $this->assertEquals($low->id, $redirects[2]->id);
    }

    public function test_source_attribute_returns_domain_for_domain_type(): void
    {
        $redirect = Redirect::factory()->domain()->create([
            'source_domain' => 'example.com',
        ]);

        $this->assertEquals('example.com', $redirect->source);
    }

    public function test_source_attribute_returns_path_for_url_type(): void
    {
        $redirect = Redirect::factory()->create([
            'source_path' => '/test-path',
        ]);

        $this->assertEquals('/test-path', $redirect->source);
    }
}

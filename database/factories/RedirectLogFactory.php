<?php

namespace Database\Factories;

use App\Models\Redirect;
use App\Models\RedirectLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RedirectLog>
 */
class RedirectLogFactory extends Factory
{
    protected $model = RedirectLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'redirect_id' => Redirect::factory(),
            'request_domain' => fake()->domainName(),
            'request_path' => '/' . fake()->slug(),
            'request_method' => 'GET',
            'request_url' => fake()->url(),
            'destination_url' => fake()->url(),
            'status_code' => 301,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'referer' => fake()->optional()->url(),
            'created_at' => now(),
        ];
    }

    /**
     * Indicate the log is for a specific redirect.
     */
    public function forRedirect(Redirect $redirect): static
    {
        return $this->state(fn (array $attributes) => [
            'redirect_id' => $redirect->id,
            'status_code' => $redirect->status_code,
        ]);
    }

    /**
     * Indicate the log is from a specific date.
     */
    public function fromDate(string|\DateTime $date): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $date,
        ]);
    }

    /**
     * Indicate the log is from mobile device.
     */
    public function mobile(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15',
        ]);
    }

    /**
     * Indicate the log has a referer.
     */
    public function withReferer(string $referer = null): static
    {
        return $this->state(fn (array $attributes) => [
            'referer' => $referer ?? fake()->url(),
        ]);
    }
}

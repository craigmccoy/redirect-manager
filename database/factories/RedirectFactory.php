<?php

namespace Database\Factories;

use App\Models\Redirect;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Redirect>
 */
class RedirectFactory extends Factory
{
    protected $model = Redirect::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_type' => 'url',
            'source_path' => '/' . fake()->slug(),
            'destination' => fake()->url(),
            'status_code' => 301,
            'priority' => 0,
            'is_active' => true,
            'preserve_path' => false,
            'preserve_query_string' => true,
            'force_https' => false,
            'case_sensitive' => false,
            'trailing_slash_mode' => null,
            'notes' => null,
        ];
    }

    /**
     * Indicate the redirect is for a domain.
     */
    public function domain(): static
    {
        return $this->state(fn (array $attributes) => [
            'source_type' => 'domain',
            'source_domain' => fake()->domainName(),
            'source_path' => null,
        ]);
    }

    /**
     * Indicate the redirect is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate the redirect uses wildcard matching.
     */
    public function wildcard(): static
    {
        return $this->state(fn (array $attributes) => [
            'source_path' => '/' . fake()->word() . '/*',
        ]);
    }

    /**
     * Indicate the redirect preserves the path.
     */
    public function preservePath(): static
    {
        return $this->state(fn (array $attributes) => [
            'preserve_path' => true,
        ]);
    }

    /**
     * Indicate the redirect forces HTTPS.
     */
    public function forceHttps(): static
    {
        return $this->state(fn (array $attributes) => [
            'force_https' => true,
        ]);
    }

    /**
     * Indicate the redirect is case-sensitive.
     */
    public function caseSensitive(): static
    {
        return $this->state(fn (array $attributes) => [
            'case_sensitive' => true,
        ]);
    }

    /**
     * Indicate the redirect adds trailing slashes.
     */
    public function addTrailingSlash(): static
    {
        return $this->state(fn (array $attributes) => [
            'trailing_slash_mode' => 'add',
        ]);
    }

    /**
     * Indicate the redirect removes trailing slashes.
     */
    public function removeTrailingSlash(): static
    {
        return $this->state(fn (array $attributes) => [
            'trailing_slash_mode' => 'remove',
        ]);
    }

    /**
     * Indicate the redirect is scheduled (currently active).
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'active_from' => now()->subDays(7),
            'active_until' => now()->addDays(7),
        ]);
    }

    /**
     * Indicate the redirect is not yet active.
     */
    public function future(): static
    {
        return $this->state(fn (array $attributes) => [
            'active_from' => now()->addDays(7),
            'active_until' => now()->addDays(14),
        ]);
    }

    /**
     * Indicate the redirect has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'active_from' => now()->subDays(14),
            'active_until' => now()->subDays(7),
        ]);
    }

    /**
     * Set a specific priority.
     */
    public function priority(int $priority): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => $priority,
        ]);
    }

    /**
     * Set a temporary redirect (302).
     */
    public function temporary(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_code' => 302,
        ]);
    }
}

<?php

namespace Rappasoft\LaravelAuthenticationLog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;

class AuthenticationLogFactory extends Factory
{
    protected $model = AuthenticationLog::class;

    public function definition(): array
    {
        return [
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'device_id' => hash('sha256', fake()->userAgent() . '|' . fake()->ipv4()),
            'device_name' => fake()->randomElement(['Chrome on Windows', 'Safari on Mac', 'Firefox on Linux', 'Edge on Windows']),
            'is_trusted' => false,
            'login_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'login_successful' => fake()->boolean(80),
            'logout_at' => fn (array $attributes) => $attributes['login_successful'] ? fake()->optional()->dateTimeBetween($attributes['login_at'], 'now') : null,
            'last_activity_at' => fn (array $attributes) => $attributes['login_successful'] && !isset($attributes['logout_at']) ? fake()->optional()->dateTimeBetween($attributes['login_at'], 'now') : null,
            'cleared_by_user' => false,
            'location' => fake()->optional()->passthrough([
                'default' => false,
                'city' => fake()->city(),
                'state' => fake()->state(),
                'country' => fake()->country(),
                'country_code' => fake()->countryCode(),
            ]),
            'is_suspicious' => false,
            'suspicious_reason' => null,
        ];
    }

    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'login_successful' => true,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'login_successful' => false,
            'logout_at' => null,
        ]);
    }

    public function loggedOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'logout_at' => fake()->dateTimeBetween($attributes['login_at'] ?? '-1 month', 'now'),
            'last_activity_at' => fn (array $attrs) => $attrs['logout_at'],
        ]);
    }

    public function trusted(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_trusted' => true,
        ]);
    }

    public function suspicious(string $reason = 'Test suspicious activity'): static
    {
        return $this->state(fn (array $attributes) => [
            'is_suspicious' => true,
            'suspicious_reason' => $reason,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'login_successful' => true,
            'logout_at' => null,
            'last_activity_at' => now(),
        ]);
    }
}

<?php

use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;
use Rappasoft\LaravelAuthenticationLog\Tests\TestUser;

beforeEach(function () {
    $this->loadLaravelMigrations();
    $this->artisan('migrate', ['--database' => 'testing'])->run();
});

it('includes failed logins from exactly 1 hour ago', function () {
    config(['authentication-log.suspicious.failed_login_threshold' => 3]);

    $user = TestUser::factory()->create();

    // Create 3 failed logins exactly 1 hour ago
    AuthenticationLog::factory()->count(3)->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => false,
        'login_at' => now()->subHour(),
    ]);

    $suspicious = $user->detectSuspiciousActivity();

    expect($suspicious)->not->toBeEmpty();
    expect($suspicious[0]['type'])->toBe('multiple_failed_logins');
    expect($suspicious[0]['count'])->toBe(3);
});

it('excludes failed logins from more than 1 hour ago', function () {
    config(['authentication-log.suspicious.failed_login_threshold' => 3]);

    $user = TestUser::factory()->create();

    // Create 3 failed logins 61 minutes ago (just over 1 hour)
    AuthenticationLog::factory()->count(3)->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => false,
        'login_at' => now()->subMinutes(61),
    ]);

    $suspicious = $user->detectSuspiciousActivity();

    expect($suspicious)->toBeEmpty();
});

it('includes failed logins from just under 1 hour ago', function () {
    config(['authentication-log.suspicious.failed_login_threshold' => 3]);

    $user = TestUser::factory()->create();

    // Create 3 failed logins 59 minutes ago (just under 1 hour)
    AuthenticationLog::factory()->count(3)->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => false,
        'login_at' => now()->subMinutes(59),
    ]);

    $suspicious = $user->detectSuspiciousActivity();

    expect($suspicious)->not->toBeEmpty();
    expect($suspicious[0]['type'])->toBe('multiple_failed_logins');
    expect($suspicious[0]['count'])->toBe(3);
});

it('includes failed logins from very recent time', function () {
    config(['authentication-log.suspicious.failed_login_threshold' => 3]);

    $user = TestUser::factory()->create();

    // Create 3 failed logins 5 minutes ago
    AuthenticationLog::factory()->count(3)->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => false,
        'login_at' => now()->subMinutes(5),
    ]);

    $suspicious = $user->detectSuspiciousActivity();

    expect($suspicious)->not->toBeEmpty();
    expect($suspicious[0]['type'])->toBe('multiple_failed_logins');
    expect($suspicious[0]['count'])->toBe(3);
});

it('correctly counts failed logins within the hour window', function () {
    config(['authentication-log.suspicious.failed_login_threshold' => 5]);

    $user = TestUser::factory()->create();

    // Create 3 failed logins within the hour
    AuthenticationLog::factory()->count(3)->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => false,
        'login_at' => now()->subMinutes(30),
    ]);

    // Create 2 failed logins outside the hour
    AuthenticationLog::factory()->count(2)->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => false,
        'login_at' => now()->subMinutes(61),
    ]);

    $suspicious = $user->detectSuspiciousActivity();

    // Should not trigger because only 3 failed logins (below threshold of 5)
    expect($suspicious)->toBeEmpty();

    // Add 2 more within the hour to trigger threshold
    AuthenticationLog::factory()->count(2)->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => false,
        'login_at' => now()->subMinutes(20),
    ]);

    $suspicious = $user->detectSuspiciousActivity();

    expect($suspicious)->not->toBeEmpty();
    expect($suspicious[0]['type'])->toBe('multiple_failed_logins');
    expect($suspicious[0]['count'])->toBe(5); // Only counts the 5 within the hour
});

it('detects rapid location changes within the last hour', function () {
    $user = TestUser::factory()->create();

    // Create 2 successful logins from different countries within the hour
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => true,
        'login_at' => now()->subMinutes(30),
        'location' => [
            'default' => false,
            'country' => 'United States',
            'city' => 'New York',
        ],
    ]);

    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => true,
        'login_at' => now()->subMinutes(20),
        'location' => [
            'default' => false,
            'country' => 'United Kingdom',
            'city' => 'London',
        ],
    ]);

    $suspicious = $user->detectSuspiciousActivity();

    expect($suspicious)->not->toBeEmpty();
    expect($suspicious[0]['type'])->toBe('rapid_location_change');
    expect($suspicious[0]['countries'])->toHaveCount(2);
});

it('excludes location changes from more than 1 hour ago', function () {
    $user = TestUser::factory()->create();

    // Create 2 successful logins from different countries, but one is over 1 hour ago
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => true,
        'login_at' => now()->subMinutes(61), // Over 1 hour ago
        'location' => [
            'default' => false,
            'country' => 'United States',
            'city' => 'New York',
        ],
    ]);

    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => true,
        'login_at' => now()->subMinutes(30),
        'location' => [
            'default' => false,
            'country' => 'United Kingdom',
            'city' => 'London',
        ],
    ]);

    $suspicious = $user->detectSuspiciousActivity();

    // Should not detect rapid location change because only 1 login within the hour
    expect($suspicious)->toBeEmpty();
});

it('includes location changes from exactly 1 hour ago', function () {
    $user = TestUser::factory()->create();

    // Create 2 successful logins from different countries, one exactly 1 hour ago
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => true,
        'login_at' => now()->subHour(), // Exactly 1 hour ago
        'location' => [
            'default' => false,
            'country' => 'United States',
            'city' => 'New York',
        ],
    ]);

    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => true,
        'login_at' => now()->subMinutes(30),
        'location' => [
            'default' => false,
            'country' => 'United Kingdom',
            'city' => 'London',
        ],
    ]);

    $suspicious = $user->detectSuspiciousActivity();

    expect($suspicious)->not->toBeEmpty();
    expect($suspicious[0]['type'])->toBe('rapid_location_change');
});

it('does not detect location changes for same country within hour', function () {
    $user = TestUser::factory()->create();

    // Create 2 successful logins from same country within the hour
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => true,
        'login_at' => now()->subMinutes(30),
        'location' => [
            'default' => false,
            'country' => 'United States',
            'city' => 'New York',
        ],
    ]);

    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => true,
        'login_at' => now()->subMinutes(20),
        'location' => [
            'default' => false,
            'country' => 'United States',
            'city' => 'Los Angeles',
        ],
    ]);

    $suspicious = $user->detectSuspiciousActivity();

    // Should not detect rapid location change for same country
    expect($suspicious)->toBeEmpty();
});

it('handles boundary condition at exactly 1 hour', function () {
    config(['authentication-log.suspicious.failed_login_threshold' => 2]);

    $user = TestUser::factory()->create();

    // Create logins at various times around the 1-hour boundary
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => false,
        'login_at' => now()->subHour()->subSecond(), // 1 hour and 1 second ago
    ]);

    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => false,
        'login_at' => now()->subHour()->addSecond(), // 59 minutes and 59 seconds ago
    ]);

    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => false,
        'login_at' => now()->subHour(), // Exactly 1 hour ago
    ]);

    $suspicious = $user->detectSuspiciousActivity();

    // Should include the login exactly 1 hour ago and the one just under
    // The one over 1 hour should be excluded
    expect($suspicious)->not->toBeEmpty();
    expect($suspicious[0]['count'])->toBe(2); // Only 2 within the hour window
});


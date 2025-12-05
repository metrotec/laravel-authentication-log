<?php

use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;
use Rappasoft\LaravelAuthenticationLog\Tests\TestUser;

beforeEach(function () {
    $this->loadLaravelMigrations();
    $this->artisan('migrate', ['--database' => 'testing'])->run();
});

it('can get login statistics', function () {
    $user = TestUser::factory()->create();

    AuthenticationLog::factory()->count(5)->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => true,
    ]);

    AuthenticationLog::factory()->count(2)->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => false,
    ]);

    $stats = $user->getLoginStats();

    expect($stats['total_logins'])->toBe(5);
    expect($stats['failed_attempts'])->toBe(2);
});

it('can get total logins', function () {
    $user = TestUser::factory()->create();

    AuthenticationLog::factory()->count(3)->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => true,
    ]);

    expect($user->getTotalLogins())->toBe(3);
});

it('can get failed attempts count', function () {
    $user = TestUser::factory()->create();

    AuthenticationLog::factory()->count(4)->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => false,
    ]);

    expect($user->getFailedAttempts())->toBe(4);
});

it('can get unique devices count', function () {
    $user = TestUser::factory()->create();

    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'device_id' => 'device-1',
        'login_successful' => true,
    ]);

    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'device_id' => 'device-2',
        'login_successful' => true,
    ]);

    expect($user->getUniqueDevicesCount())->toBe(2);
});

it('can get suspicious activities count', function () {
    $user = TestUser::factory()->create();

    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'is_suspicious' => true,
    ]);

    expect($user->getSuspiciousActivitiesCount())->toBe(1);
});

<?php

use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;
use Rappasoft\LaravelAuthenticationLog\Tests\TestUser;

beforeEach(function () {
    $this->loadLaravelMigrations();
    $this->artisan('migrate', ['--database' => 'testing'])->run();
});

it('can get active sessions', function () {
    $user = TestUser::factory()->create();

    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => true,
        'logout_at' => null,
    ]);

    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => true,
        'logout_at' => now(),
    ]);

    expect($user->getActiveSessionsCount())->toBe(1);
    expect($user->getActiveSessions()->count())->toBe(1);
});

it('can revoke a specific session', function () {
    $user = TestUser::factory()->create();

    $session = AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => true,
        'logout_at' => null,
    ]);

    expect($user->revokeSession($session->id))->toBeTrue();

    $session->refresh();
    expect($session->logout_at)->not->toBeNull();
    expect($session->cleared_by_user)->toBeTrue();
});

it('can revoke all other sessions', function () {
    $user = TestUser::factory()->create();
    $currentDeviceId = 'current-device';

    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'device_id' => $currentDeviceId,
        'login_successful' => true,
        'logout_at' => null,
    ]);

    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'device_id' => 'other-device-1',
        'login_successful' => true,
        'logout_at' => null,
    ]);

    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'device_id' => 'other-device-2',
        'login_successful' => true,
        'logout_at' => null,
    ]);

    expect($user->revokeAllOtherSessions($currentDeviceId))->toBe(2);
    expect($user->getActiveSessionsCount())->toBe(1);
});

it('can revoke all sessions', function () {
    $user = TestUser::factory()->create();

    AuthenticationLog::factory()->count(3)->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => true,
        'logout_at' => null,
    ]);

    expect($user->revokeAllSessions())->toBe(3);
    expect($user->getActiveSessionsCount())->toBe(0);
});

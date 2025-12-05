<?php

use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;
use Rappasoft\LaravelAuthenticationLog\Tests\TestUser;

beforeEach(function () {
    $this->loadLaravelMigrations();
    $this->artisan('migrate', ['--database' => 'testing'])->run();
});

it('can filter successful logins', function () {
    $user = TestUser::factory()->create();
    
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => true,
    ]);
    
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => false,
    ]);
    
    expect(AuthenticationLog::successful()->count())->toBe(1);
    expect(AuthenticationLog::failed()->count())->toBe(1);
});

it('can filter by ip address', function () {
    $user = TestUser::factory()->create();
    
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'ip_address' => '192.168.1.1',
    ]);
    
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'ip_address' => '192.168.1.2',
    ]);
    
    expect(AuthenticationLog::fromIp('192.168.1.1')->count())->toBe(1);
});

it('can filter recent logs', function () {
    $user = TestUser::factory()->create();
    
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_at' => now()->subDays(10),
    ]);
    
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_at' => now()->subDays(2),
    ]);
    
    expect(AuthenticationLog::recent(7)->count())->toBe(1);
});

it('can filter suspicious logs', function () {
    $user = TestUser::factory()->create();
    
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'is_suspicious' => true,
    ]);
    
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'is_suspicious' => false,
    ]);
    
    expect(AuthenticationLog::suspicious()->count())->toBe(1);
});

it('can filter active sessions', function () {
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
    
    expect(AuthenticationLog::active()->count())->toBe(1);
});

it('can filter trusted devices', function () {
    $user = TestUser::factory()->create();
    
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'is_trusted' => true,
    ]);
    
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'is_trusted' => false,
    ]);
    
    expect(AuthenticationLog::trusted()->count())->toBe(1);
});

it('can filter by device id', function () {
    $user = TestUser::factory()->create();
    $deviceId = 'test-device-123';
    
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'device_id' => $deviceId,
    ]);
    
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'device_id' => 'other-device',
    ]);
    
    expect(AuthenticationLog::fromDevice($deviceId)->count())->toBe(1);
});

it('can filter for specific user', function () {
    $user1 = TestUser::factory()->create();
    $user2 = TestUser::factory()->create();
    
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user1),
        'authenticatable_id' => $user1->id,
    ]);
    
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user2),
        'authenticatable_id' => $user2->id,
    ]);
    
    expect(AuthenticationLog::forUser($user1)->count())->toBe(1);
});


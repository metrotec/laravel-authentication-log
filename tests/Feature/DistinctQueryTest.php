<?php

use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;
use Rappasoft\LaravelAuthenticationLog\Tests\TestUser;

beforeEach(function () {
    $this->loadLaravelMigrations();
    $this->artisan('migrate', ['--database' => 'testing'])->run();
});

it('correctly counts distinct device_ids', function () {
    $user = TestUser::factory()->create();

    // Create multiple logs with same device_id
    AuthenticationLog::factory()->count(3)->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'device_id' => 'device-1',
        'login_successful' => true,
    ]);

    // Create logs with different device_ids
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'device_id' => 'device-2',
        'login_successful' => true,
    ]);

    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'device_id' => 'device-3',
        'login_successful' => true,
    ]);

    // Should count 3 distinct devices, not 5 total logs
    expect($user->getUniqueDevicesCount())->toBe(3);
    expect($user->getLoginStats()['unique_devices'])->toBe(3);
});

it('correctly counts distinct ip_addresses', function () {
    $user = TestUser::factory()->create();

    // Create multiple logs with same IP
    AuthenticationLog::factory()->count(2)->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'ip_address' => '192.168.1.1',
        'login_successful' => true,
    ]);

    // Create logs with different IPs
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'ip_address' => '192.168.1.2',
        'login_successful' => true,
    ]);

    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'ip_address' => '192.168.1.3',
        'login_successful' => true,
    ]);

    // Should count 3 distinct IPs, not 4 total logs
    expect($user->getLoginStats()['unique_ips'])->toBe(3);
});

it('getDevices returns distinct devices with latest information', function () {
    $user = TestUser::factory()->create();

    // Create multiple logs for same device with different names
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'device_id' => 'device-1',
        'device_name' => 'Old Name',
        'login_successful' => true,
        'login_at' => now()->subDays(5),
    ]);

    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'device_id' => 'device-1',
        'device_name' => 'New Name',
        'login_successful' => true,
        'login_at' => now()->subDays(1),
    ]);

    // Create another device
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'device_id' => 'device-2',
        'device_name' => 'Device 2',
        'login_successful' => true,
        'login_at' => now()->subDays(2),
    ]);

    $devices = $user->getDevices();

    // Should return 2 distinct devices
    expect($devices->count())->toBe(2);
    
    // Should have the latest device name for device-1
    $device1 = $devices->firstWhere('device_id', 'device-1');
    expect($device1->device_name)->toBe('New Name');
});

it('handles null device_ids correctly in distinct count', function () {
    $user = TestUser::factory()->create();

    // Create logs with null device_id
    AuthenticationLog::factory()->count(2)->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'device_id' => null,
        'login_successful' => true,
    ]);

    // Create logs with actual device_ids
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'device_id' => 'device-1',
        'login_successful' => true,
    ]);

    // Should only count non-null device_ids
    expect($user->getUniqueDevicesCount())->toBe(1);
});


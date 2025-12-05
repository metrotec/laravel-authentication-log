<?php

use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;
use Rappasoft\LaravelAuthenticationLog\Tests\TestUser;

beforeEach(function () {
    $this->loadLaravelMigrations();
    $this->artisan('migrate', ['--database' => 'testing'])->run();
});

it('can get user devices', function () {
    $user = TestUser::factory()->create();
    
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'device_id' => 'device-1',
        'device_name' => 'Chrome on Windows',
        'login_successful' => true,
    ]);
    
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'device_id' => 'device-2',
        'device_name' => 'Safari on Mac',
        'login_successful' => true,
    ]);
    
    $devices = $user->getDevices();
    
    expect($devices->count())->toBe(2);
    expect($devices->pluck('device_id')->toArray())->toContain('device-1', 'device-2');
});

it('can trust a device', function () {
    $user = TestUser::factory()->create();
    $deviceId = 'device-123';
    
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'device_id' => $deviceId,
        'is_trusted' => false,
    ]);
    
    expect($user->trustDevice($deviceId))->toBeTrue();
    expect($user->isDeviceTrusted($deviceId))->toBeTrue();
});

it('can untrust a device', function () {
    $user = TestUser::factory()->create();
    $deviceId = 'device-123';
    
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'device_id' => $deviceId,
        'is_trusted' => true,
    ]);
    
    expect($user->untrustDevice($deviceId))->toBeTrue();
    expect($user->isDeviceTrusted($deviceId))->toBeFalse();
});

it('can update device name', function () {
    $user = TestUser::factory()->create();
    $deviceId = 'device-123';
    
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'device_id' => $deviceId,
        'device_name' => 'Old Name',
    ]);
    
    expect($user->updateDeviceName($deviceId, 'New Name'))->toBeTrue();
    
    $log = AuthenticationLog::fromDevice($deviceId)->first();
    expect($log->device_name)->toBe('New Name');
});


<?php

use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;
use Rappasoft\LaravelAuthenticationLog\Tests\TestUser;

beforeEach(function () {
    $this->loadLaravelMigrations();
    $this->artisan('migrate', ['--database' => 'testing'])->run();
});

it('has correct fillable attributes', function () {
    $fillable = [
        'ip_address',
        'user_agent',
        'device_id',
        'device_name',
        'is_trusted',
        'login_at',
        'login_successful',
        'logout_at',
        'last_activity_at',
        'cleared_by_user',
        'location',
        'is_suspicious',
        'suspicious_reason',
    ];

    expect((new AuthenticationLog())->getFillable())->toBe($fillable);
});

it('has correct casts', function () {
    $log = new AuthenticationLog();

    expect($log->getCasts())->toHaveKey('cleared_by_user', 'boolean');
    expect($log->getCasts())->toHaveKey('is_trusted', 'boolean');
    expect($log->getCasts())->toHaveKey('is_suspicious', 'boolean');
    expect($log->getCasts())->toHaveKey('location', 'array');
    expect($log->getCasts())->toHaveKey('login_successful', 'boolean');
    expect($log->getCasts())->toHaveKey('login_at', 'datetime');
    expect($log->getCasts())->toHaveKey('logout_at', 'datetime');
    expect($log->getCasts())->toHaveKey('last_activity_at', 'datetime');
});

it('has default attributes', function () {
    $log = new AuthenticationLog();

    expect($log->login_successful)->toBeFalse();
    expect($log->cleared_by_user)->toBeFalse();
    expect($log->is_trusted)->toBeFalse();
    expect($log->is_suspicious)->toBeFalse();
});

it('can have authenticatable relationship', function () {
    $user = TestUser::factory()->create();
    $log = AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
    ]);

    expect($log->authenticatable)->toBeInstanceOf(TestUser::class);
    expect($log->authenticatable->id)->toBe($user->id);
});

it('can use custom table name', function () {
    config(['authentication-log.table_name' => 'custom_auth_log']);

    $log = new AuthenticationLog();
    expect($log->getTable())->toBe('custom_auth_log');
});

it('can use custom database connection', function () {
    config(['authentication-log.db_connection' => 'custom']);

    $log = new AuthenticationLog();
    expect($log->getConnectionName())->toBe('custom');
});

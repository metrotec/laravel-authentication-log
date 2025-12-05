<?php

use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;
use Rappasoft\LaravelAuthenticationLog\Tests\TestUser;

beforeEach(function () {
    $this->loadLaravelMigrations();
    $this->artisan('migrate', ['--database' => 'testing'])->run();
});

it('can detect multiple failed logins', function () {
    config(['authentication-log.suspicious.failed_login_threshold' => 3]);
    
    $user = TestUser::factory()->create();
    
    // Create 4 failed logins in the last hour
    AuthenticationLog::factory()->count(4)->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => false,
        'login_at' => now()->subMinutes(30),
    ]);
    
    $suspicious = $user->detectSuspiciousActivity();
    
    expect($suspicious)->not->toBeEmpty();
    expect($suspicious[0]['type'])->toBe('multiple_failed_logins');
});

it('can mark log as suspicious', function () {
    $user = TestUser::factory()->create();
    
    $log = AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'is_suspicious' => false,
    ]);
    
    $log->markAsSuspicious('Test reason');
    
    $log->refresh();
    expect($log->is_suspicious)->toBeTrue();
    expect($log->suspicious_reason)->toBe('Test reason');
});

it('can check if log is active', function () {
    $user = TestUser::factory()->create();
    
    $activeLog = AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => true,
        'logout_at' => null,
    ]);
    
    $inactiveLog = AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_successful' => true,
        'logout_at' => now(),
    ]);
    
    expect($activeLog->isActive())->toBeTrue();
    expect($inactiveLog->isActive())->toBeFalse();
});


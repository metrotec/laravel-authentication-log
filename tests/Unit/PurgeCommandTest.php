<?php

use Rappasoft\LaravelAuthenticationLog\Commands\PurgeAuthenticationLogCommand;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;
use Rappasoft\LaravelAuthenticationLog\Tests\TestUser;

beforeEach(function () {
    $this->loadLaravelMigrations();
    $this->artisan('migrate', ['--database' => 'testing'])->run();
});

it('can purge old authentication logs', function () {
    $user = TestUser::factory()->create();
    
    // Create old log
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_at' => now()->subDays(400),
    ]);
    
    // Create recent log
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_at' => now()->subDays(100),
    ]);
    
    config(['authentication-log.purge' => 365]);
    
    $this->artisan(PurgeAuthenticationLogCommand::class)->assertSuccessful();
    
    expect(AuthenticationLog::count())->toBe(1);
    expect(AuthenticationLog::first()->login_at->isAfter(now()->subDays(365)))->toBeTrue();
});

it('respects custom purge days', function () {
    $user = TestUser::factory()->create();
    
    // Create log older than 30 days
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_at' => now()->subDays(50),
    ]);
    
    // Create log within 30 days
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_at' => now()->subDays(10),
    ]);
    
    config(['authentication-log.purge' => 30]);
    
    $this->artisan(PurgeAuthenticationLogCommand::class)->assertSuccessful();
    
    expect(AuthenticationLog::count())->toBe(1);
});


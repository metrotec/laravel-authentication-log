<?php

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\OtherDeviceLogout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;
use Rappasoft\LaravelAuthenticationLog\Notifications\FailedLogin;
use Rappasoft\LaravelAuthenticationLog\Notifications\NewDevice;
use Rappasoft\LaravelAuthenticationLog\Tests\TestUser;

beforeEach(function () {
    $this->loadLaravelMigrations();
    $this->artisan('migrate', ['--database' => 'testing'])->run();
});

it('logs successful login', function () {
    $user = TestUser::factory()->create();

    Event::dispatch(new Login('web', $user, false));

    expect($user->authentications()->count())->toBe(1);

    $log = $user->authentications()->first();
    expect($log->login_successful)->toBeTrue();
    expect($log->login_at)->not->toBeNull();
    expect($log->ip_address)->not->toBeNull();
    expect($log->user_agent)->not->toBeNull();
});

it('logs failed login', function () {
    $user = TestUser::factory()->create();

    Event::dispatch(new Failed('web', $user, []));

    expect($user->authentications()->count())->toBe(1);

    $log = $user->authentications()->first();
    expect($log->login_successful)->toBeFalse();
    expect($log->login_at)->not->toBeNull();
});

it('logs logout', function () {
    $user = TestUser::factory()->create();

    // Set current IP and user agent to match the log
    $ip = '127.0.0.1';
    $userAgent = 'Test Browser';
    request()->server->set('REMOTE_ADDR', $ip);
    request()->headers->set('User-Agent', $userAgent);

    // Create a login log first with matching IP and user agent
    $log = AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'ip_address' => $ip,
        'user_agent' => $userAgent,
        'login_at' => now()->subHour(),
        'login_successful' => true,
    ]);

    Event::dispatch(new Logout('web', $user));

    $log->refresh();
    expect($log->logout_at)->not->toBeNull();
});

it('sends new device notification when logging in from new device', function () {
    Notification::fake();

    // Create user that's not "new" (older than 1 minute)
    $user = TestUser::factory()->create([
        'created_at' => now()->subMinutes(2),
    ]);

    // First login - this creates a known device
    request()->server->set('REMOTE_ADDR', '192.168.1.1');
    request()->headers->set('User-Agent', 'Browser 1');
    Event::dispatch(new Login('web', $user, false));

    // Clear notifications from first login
    Notification::fake();

    // Simulate different IP/User Agent for second login
    request()->server->set('REMOTE_ADDR', '192.168.1.100');
    request()->headers->set('User-Agent', 'Different Browser');

    // Second login from different device
    Event::dispatch(new Login('web', $user, false));

    Notification::assertSentTo($user, NewDevice::class);
});

it('does not send new device notification for new users', function () {
    Notification::fake();

    $user = TestUser::factory()->create([
        'created_at' => now(),
    ]);

    Event::dispatch(new Login('web', $user, false));

    Notification::assertNothingSent();
});

it('respects configurable new user threshold', function () {
    Notification::fake();

    // Set threshold to 5 minutes
    config(['authentication-log.notifications.new-device.new_user_threshold_minutes' => 5]);

    // Create user 3 minutes ago (within threshold)
    $user = TestUser::factory()->create([
        'created_at' => now()->subMinutes(3),
    ]);

    // First login - should not send notification (user is still "new")
    Event::dispatch(new Login('web', $user, false));
    Notification::assertNothingSent();

    // Create a new user that's outside the threshold to test properly
    $oldUser = TestUser::factory()->create([
        'created_at' => now()->subMinutes(6),
    ]);

    Notification::fake();

    // Login from different device
    request()->server->set('REMOTE_ADDR', '192.168.1.100');
    request()->headers->set('User-Agent', 'Different Browser');
    Event::dispatch(new Login('web', $oldUser, false));

    // Should send notification now (user is no longer "new")
    Notification::assertSentTo($oldUser, NewDevice::class);
});

it('sends failed login notification when enabled', function () {
    Notification::fake();

    config(['authentication-log.notifications.failed-login.enabled' => true]);

    $user = TestUser::factory()->create();

    Event::dispatch(new Failed('web', $user, []));

    Notification::assertSentTo($user, FailedLogin::class);
});

it('does not send failed login notification when disabled', function () {
    Notification::fake();

    config(['authentication-log.notifications.failed-login.enabled' => false]);

    $user = TestUser::factory()->create();

    Event::dispatch(new Failed('web', $user, []));

    Notification::assertNothingSent();
});

it('handles other device logout', function () {
    $user = TestUser::factory()->create();

    // Set current IP and user agent for the "current" session
    request()->server->set('REMOTE_ADDR', '192.168.1.2');
    request()->headers->set('User-Agent', 'Current Browser');

    // Create log for current session (should NOT be cleared)
    $currentLog = AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'ip_address' => '192.168.1.2',
        'user_agent' => 'Current Browser',
        'login_at' => now()->subHour(),
        'login_successful' => true,
        'logout_at' => null,
    ]);

    // Create log for other session (should be cleared)
    $otherLog = AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'ip_address' => '192.168.1.1',
        'user_agent' => 'Other Browser',
        'login_at' => now()->subHours(2),
        'login_successful' => true,
        'logout_at' => null,
    ]);

    Event::dispatch(new OtherDeviceLogout('web', $user));

    $currentLog->refresh();
    $otherLog->refresh();

    expect($otherLog->cleared_by_user)->toBeTrue();
    expect($otherLog->logout_at)->not->toBeNull();
    expect($currentLog->cleared_by_user)->toBeFalse();
    expect($currentLog->logout_at)->toBeNull();
});

it('can get latest authentication', function () {
    $user = TestUser::factory()->create();

    $oldLog = AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_at' => now()->subDays(2),
    ]);

    $newLog = AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_at' => now()->subDay(),
    ]);

    expect($user->latestAuthentication->id)->toBe($newLog->id);
});

it('can get last login at', function () {
    $user = TestUser::factory()->create();

    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_at' => now()->subDay(),
    ]);

    expect($user->lastLoginAt())->not->toBeNull();
    expect($user->lastLoginAt()->isToday())->toBeFalse();
});

it('can get last successful login at', function () {
    $user = TestUser::factory()->create();

    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_at' => now()->subDay(),
        'login_successful' => false,
    ]);

    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_at' => now()->subHours(2),
        'login_successful' => true,
    ]);

    expect($user->lastSuccessfulLoginAt())->not->toBeNull();

    // Also test that it returns the most recent successful login
    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_at' => now()->subHour(),
        'login_successful' => false,
    ]);

    // Should still return the successful one
    expect($user->lastSuccessfulLoginAt())->not->toBeNull();

    // Add a more recent successful login
    $recentLogin = AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_at' => now()->subMinutes(30),
        'login_successful' => true,
    ]);

    // Should return the most recent successful login
    expect($user->lastSuccessfulLoginAt())->not->toBeNull();
    expect($user->lastSuccessfulLoginAt()->format('Y-m-d H:i:s'))->toBe($recentLogin->login_at->format('Y-m-d H:i:s'));
});

it('can get previous login ip', function () {
    $user = TestUser::factory()->create();

    $firstIp = '192.168.1.1';
    $secondIp = '192.168.1.2';

    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_at' => now()->subDays(2),
        'ip_address' => $firstIp,
    ]);

    AuthenticationLog::factory()->create([
        'authenticatable_type' => get_class($user),
        'authenticatable_id' => $user->id,
        'login_at' => now()->subDay(),
        'ip_address' => $secondIp,
    ]);

    expect($user->previousLoginIp())->toBe($firstIp);
});

it('respects cdn configuration', function () {
    config(['authentication-log.behind_cdn' => [
        'http_header_field' => 'HTTP_CF_CONNECTING_IP',
    ]]);

    request()->server->set('HTTP_CF_CONNECTING_IP', '1.2.3.4');

    $user = TestUser::factory()->create();

    Event::dispatch(new Login('web', $user, false));

    $log = $user->authentications()->first();
    expect($log->ip_address)->toBe('1.2.3.4');
});

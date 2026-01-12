<?php

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Notification;
use Rappasoft\LaravelAuthenticationLog\Notifications\NewDevice;
use Rappasoft\LaravelAuthenticationLog\Tests\TestUser;

beforeEach(function () {
    $this->loadLaravelMigrations();
    $this->artisan('migrate', ['--database' => 'testing'])->run();
});

it('does not send new device notification when disabled in config', function () {
    Notification::fake();

    config(['authentication-log.notifications.new-device.enabled' => false]);

    $user = TestUser::factory()->create([
        'created_at' => now()->subMinutes(10), // Not a new user
    ]);

    // Login from a new device (no previous logins)
    request()->server->set('REMOTE_ADDR', '192.168.1.1');
    request()->headers->set('User-Agent', 'New Device Browser');
    Event::dispatch(new Login('web', $user, false));

    // Should NOT send notification because it's disabled
    Notification::assertNothingSent();
});

it('sends new device notification when enabled in config', function () {
    Notification::fake();

    config(['authentication-log.notifications.new-device.enabled' => true]);

    $user = TestUser::factory()->create([
        'created_at' => now()->subMinutes(10), // Not a new user
    ]);

    // Login from a new device (no previous logins)
    request()->server->set('REMOTE_ADDR', '192.168.1.1');
    request()->headers->set('User-Agent', 'New Device Browser');
    Event::dispatch(new Login('web', $user, false));

    // Should send notification because it's enabled
    Notification::assertSentTo($user, NewDevice::class);
});

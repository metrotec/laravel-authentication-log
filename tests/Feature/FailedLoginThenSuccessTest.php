<?php

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Rappasoft\LaravelAuthenticationLog\Notifications\NewDevice;
use Rappasoft\LaravelAuthenticationLog\Tests\TestUser;

beforeEach(function () {
    $this->loadLaravelMigrations();
    $this->artisan('migrate', ['--database' => 'testing'])->run();
});

it('sends new device notification after failed login on unknown device', function () {
    Notification::fake();

    $user = TestUser::factory()->create([
        'created_at' => now()->subMinutes(2),
    ]);

    // Failed login attempt from new device
    request()->server->set('REMOTE_ADDR', '192.168.1.1');
    request()->headers->set('User-Agent', 'New Device Browser');
    Event::dispatch(new Failed('web', $user, []));

    Notification::fake();

    // Successful login from same device that had failed login
    Event::dispatch(new Login('web', $user, false));

    // Should send notification because:
    // 1. Device is not known (no previous successful login on this device)
    // 2. There was a failed login on this device (security concern)
    Notification::assertSentTo($user, NewDevice::class);
});

it('does not send notification if device already had successful login', function () {
    Notification::fake();

    $user = TestUser::factory()->create([
        'created_at' => now()->subMinutes(2),
    ]);

    // Set up device
    request()->server->set('REMOTE_ADDR', '192.168.1.1');
    request()->headers->set('User-Agent', 'Known Device Browser');

    // First successful login (device becomes known)
    Event::dispatch(new Login('web', $user, false));

    Notification::fake();

    // Second successful login from same device (no notification expected)
    Event::dispatch(new Login('web', $user, false));

    // Should NOT send notification because device is already known
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

<?php

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Rappasoft\LaravelAuthenticationLog\Helpers\DeviceFingerprint;
use Rappasoft\LaravelAuthenticationLog\Notifications\NewDevice;
use Rappasoft\LaravelAuthenticationLog\Tests\TestUser;

beforeEach(function () {
    $this->loadLaravelMigrations();
    $this->artisan('migrate', ['--database' => 'testing'])->run();
});

it('generates consistent device fingerprint', function () {
    request()->server->set('REMOTE_ADDR', '192.168.1.1');
    request()->headers->set('User-Agent', 'Test Browser');

    $fingerprint1 = DeviceFingerprint::generate(request());
    $fingerprint2 = DeviceFingerprint::generate(request());

    expect($fingerprint1)->toBe($fingerprint2);
});

it('generates different fingerprints for different devices', function () {
    request()->server->set('REMOTE_ADDR', '192.168.1.1');
    request()->headers->set('User-Agent', 'Browser 1');

    $fingerprint1 = DeviceFingerprint::generate(request());

    request()->headers->set('User-Agent', 'Browser 2');

    $fingerprint2 = DeviceFingerprint::generate(request());

    expect($fingerprint1)->not->toBe($fingerprint2);
});

it('generates same fingerprint for browser version updates', function () {
    // Safari 14.1.2
    request()->server->set('REMOTE_ADDR', '192.168.1.1');
    request()->headers->set('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.2 Safari/605.1.15');

    $fingerprint1 = DeviceFingerprint::generate(request());

    // Safari 15.1 (updated version)
    request()->headers->set('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.1 Safari/605.1.15');

    $fingerprint2 = DeviceFingerprint::generate(request());

    // Should be the same fingerprint despite version change
    expect($fingerprint1)->toBe($fingerprint2);
});

it('generates same fingerprint for Chrome version updates', function () {
    request()->server->set('REMOTE_ADDR', '192.168.1.1');

    // Chrome 120.0.0.0
    request()->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

    $fingerprint1 = DeviceFingerprint::generate(request());

    // Chrome 121.0.0.0 (updated version)
    request()->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36');

    $fingerprint2 = DeviceFingerprint::generate(request());

    // Should be the same fingerprint despite version change
    expect($fingerprint1)->toBe($fingerprint2);
});

it('generates different fingerprints for different browsers', function () {
    request()->server->set('REMOTE_ADDR', '192.168.1.1');

    // Chrome
    request()->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    $fingerprint1 = DeviceFingerprint::generate(request());

    // Firefox
    request()->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0');
    $fingerprint2 = DeviceFingerprint::generate(request());

    // Should be different
    expect($fingerprint1)->not->toBe($fingerprint2);
});

it('generates different fingerprints for different IPs', function () {
    request()->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

    request()->server->set('REMOTE_ADDR', '192.168.1.1');
    $fingerprint1 = DeviceFingerprint::generate(request());

    request()->server->set('REMOTE_ADDR', '192.168.1.2');
    $fingerprint2 = DeviceFingerprint::generate(request());

    // Should be different
    expect($fingerprint1)->not->toBe($fingerprint2);
});

it('generates different fingerprints for different operating systems', function () {
    request()->server->set('REMOTE_ADDR', '192.168.1.1');

    // Windows
    request()->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    $fingerprint1 = DeviceFingerprint::generate(request());

    // Mac
    request()->headers->set('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    $fingerprint2 = DeviceFingerprint::generate(request());

    // Should be different
    expect($fingerprint1)->not->toBe($fingerprint2);
});

it('does not send new device notification on browser version update', function () {
    Notification::fake();

    $user = TestUser::factory()->create([
        'created_at' => now()->subMinutes(2),
    ]);

    // First login with Safari 14.1.2
    request()->server->set('REMOTE_ADDR', '192.168.1.1');
    request()->headers->set('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.2 Safari/605.1.15');
    Event::dispatch(new Login('web', $user, false));

    // Clear notifications from first login
    Notification::fake();

    // Second login with Safari 15.1 (updated version, same device)
    request()->headers->set('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.1 Safari/605.1.15');
    Event::dispatch(new Login('web', $user, false));

    // Should NOT send new device notification because fingerprint is the same
    Notification::assertNothingSent();
});

it('sends new device notification when browser actually changes', function () {
    Notification::fake();

    $user = TestUser::factory()->create([
        'created_at' => now()->subMinutes(2),
    ]);

    // First login with Chrome
    request()->server->set('REMOTE_ADDR', '192.168.1.1');
    request()->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    Event::dispatch(new Login('web', $user, false));

    // Clear notifications from first login
    Notification::fake();

    // Second login with Firefox (different browser)
    request()->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0');
    Event::dispatch(new Login('web', $user, false));

    // Should send new device notification because fingerprint is different
    Notification::assertSentTo($user, NewDevice::class);
});

it('generates device name from user agent', function () {
    request()->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

    $deviceName = DeviceFingerprint::generateDeviceName(request());

    expect($deviceName)->toContain('Windows');
});

it('stores device fingerprint on login', function () {
    $user = TestUser::factory()->create();

    request()->server->set('REMOTE_ADDR', '192.168.1.1');
    request()->headers->set('User-Agent', 'Test Browser');

    Event::dispatch(new Login('web', $user, false));

    $log = $user->authentications()->first();
    expect($log->device_id)->not->toBeNull();
    expect($log->device_name)->not->toBeNull();
});

<?php

use Illuminate\Support\Facades\Cache;
use Rappasoft\LaravelAuthenticationLog\Helpers\NotificationRateLimiter;

beforeEach(function () {
    Cache::flush();
});

it('allows sending notification within rate limit', function () {
    $key = 'test:notification';

    expect(NotificationRateLimiter::shouldSend($key, 3, 60))->toBeTrue();
    expect(NotificationRateLimiter::shouldSend($key, 3, 60))->toBeTrue();
    expect(NotificationRateLimiter::shouldSend($key, 3, 60))->toBeTrue();
});

it('blocks notification after rate limit exceeded', function () {
    $key = 'test:notification';

    NotificationRateLimiter::shouldSend($key, 2, 60);
    NotificationRateLimiter::shouldSend($key, 2, 60);

    expect(NotificationRateLimiter::shouldSend($key, 2, 60))->toBeFalse();
});

it('can reset rate limit', function () {
    $key = 'test:notification';

    NotificationRateLimiter::shouldSend($key, 2, 60);
    NotificationRateLimiter::shouldSend($key, 2, 60);

    expect(NotificationRateLimiter::shouldSend($key, 2, 60))->toBeFalse();

    NotificationRateLimiter::reset($key);

    expect(NotificationRateLimiter::shouldSend($key, 2, 60))->toBeTrue();
});

it('can get remaining attempts', function () {
    $key = 'test:notification';

    expect(NotificationRateLimiter::getRemainingAttempts($key, 3))->toBe(3);

    NotificationRateLimiter::shouldSend($key, 3, 60);

    expect(NotificationRateLimiter::getRemainingAttempts($key, 3))->toBe(2);
});

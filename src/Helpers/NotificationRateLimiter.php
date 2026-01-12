<?php

namespace Rappasoft\LaravelAuthenticationLog\Helpers;

use Illuminate\Support\Facades\Cache;

class NotificationRateLimiter
{
    public static function shouldSend(string $key, int $maxAttempts = 3, int $decayMinutes = 60): bool
    {
        $cacheKey = "auth_log_notification:{$key}";
        $attempts = Cache::get($cacheKey, 0);

        if ($attempts >= $maxAttempts) {
            return false;
        }

        Cache::put($cacheKey, $attempts + 1, now()->addMinutes($decayMinutes));

        return true;
    }

    public static function reset(string $key): void
    {
        Cache::forget("auth_log_notification:{$key}");
    }

    public static function getRemainingAttempts(string $key, int $maxAttempts = 3): int
    {
        $cacheKey = "auth_log_notification:{$key}";
        $attempts = Cache::get($cacheKey, 0);

        return max(0, $maxAttempts - $attempts);
    }
}

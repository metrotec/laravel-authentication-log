<?php

namespace Rappasoft\LaravelAuthenticationLog\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Rappasoft\LaravelAuthenticationLog\Helpers\DeviceFingerprint;
use Rappasoft\LaravelAuthenticationLog\Helpers\NotificationRateLimiter;
use Rappasoft\LaravelAuthenticationLog\Notifications\FailedLogin;
use Rappasoft\LaravelAuthenticationLog\Notifications\SuspiciousActivity;
use Rappasoft\LaravelAuthenticationLog\Services\WebhookService;
use Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable;
use Rappasoft\LaravelAuthenticationLog\Traits\ParsesUserAgent;

class FailedLoginListener
{
    use ParsesUserAgent;

    public Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function handle(Failed $event): void
    {
        if ($event->user instanceof Authenticatable) {
            /** @var Authenticatable&\Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable $user */
            $user = $event->user;

            if (! in_array(AuthenticationLoggable::class, class_uses_recursive(get_class($user)))) {
                return;
            }

            if (config('authentication-log.behind_cdn')) {
                $ip = $this->request->server(config('authentication-log.behind_cdn.http_header_field'));
            } else {
                $ip = $this->request->ip();
            }

            $deviceId = DeviceFingerprint::generate($this->request);
            $deviceName = DeviceFingerprint::generateDeviceName($this->request);

            $log = $user->authentications()->create([
                'ip_address' => $ip,
                'user_agent' => $this->parseUserAgent($this->request->userAgent()),
                'device_id' => $deviceId,
                'device_name' => $deviceName,
                'login_at' => now(),
                'login_successful' => false,
                'location' => config('authentication-log.notifications.failed-login.location') && function_exists('geoip') ? (geoip()->getLocation($ip)?->toArray()) : null,
            ]);

            // Check for suspicious activity (multiple failed logins in the last hour)
            $recentFailed = $user->authentications()
                ->failed()
                ->where('login_at', '>=', now()->subHour())
                ->count();

            $isSuspicious = false;
            $suspiciousActivities = [];

            if ($recentFailed >= config('authentication-log.suspicious.failed_login_threshold', 5)) {
                $isSuspicious = true;
                $suspiciousActivities = [
                    [
                        'type' => 'multiple_failed_logins',
                        'count' => $recentFailed,
                        'message' => "{$recentFailed} failed login attempts in the last hour",
                    ],
                ];
                $log->markAsSuspicious("Multiple failed logins: {$recentFailed} attempts in the last hour");
            }

            // Send failed login notification with rate limiting
            if (config('authentication-log.notifications.failed-login.enabled')) {
                $rateLimitKey = "failed_login:{$user->id}";
                $maxAttempts = config('authentication-log.notifications.failed-login.rate_limit', 5);
                $decayMinutes = config('authentication-log.notifications.failed-login.rate_limit_decay', 60);

                if (NotificationRateLimiter::shouldSend($rateLimitKey, $maxAttempts, $decayMinutes)) {
                    $failedLoginClass = config('authentication-log.notifications.failed-login.template') ?? FailedLogin::class;
                    $user->notify(new $failedLoginClass($log));
                }
            }

            // Send suspicious activity notification with rate limiting
            if ($isSuspicious && config('authentication-log.notifications.suspicious-activity.enabled')) {
                $rateLimitKey = "suspicious_activity:{$user->id}";
                $maxAttempts = config('authentication-log.notifications.suspicious-activity.rate_limit', 3);
                $decayMinutes = config('authentication-log.notifications.suspicious-activity.rate_limit_decay', 60);

                if (NotificationRateLimiter::shouldSend($rateLimitKey, $maxAttempts, $decayMinutes)) {
                    $suspiciousActivityClass = config('authentication-log.notifications.suspicious-activity.template') ?? SuspiciousActivity::class;
                    $user->notify(new $suspiciousActivityClass($log, $suspiciousActivities));
                }
            }

            // Send webhook
            WebhookService::send('failed', $log, $user);

            if ($log->is_suspicious) {
                WebhookService::send('suspicious', $log, $user);
            }
        }
    }
}

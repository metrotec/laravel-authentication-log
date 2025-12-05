<?php

namespace Rappasoft\LaravelAuthenticationLog\Listeners;

use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Rappasoft\LaravelAuthenticationLog\Helpers\DeviceFingerprint;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;
use Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable;

class LogoutListener
{
    public Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function handle(Logout $event): void
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

            $userAgent = $this->request->userAgent();
            $deviceId = DeviceFingerprint::generate($this->request);
            
            // Try to find by device_id first (more reliable), then fall back to IP+UA
            $log = $user->authentications()->fromDevice($deviceId)->orderByDesc('login_at')->first();
            
            if (! $log) {
                $log = $user->authentications()->whereIpAddress($ip)->whereUserAgent($userAgent)->orderByDesc('login_at')->first();
            }

            if (! $log) {
                $log = new AuthenticationLog([
                    'ip_address' => $ip,
                    'user_agent' => $userAgent,
                    'device_id' => $deviceId,
                ]);
            }

            $log->logout_at = now();
            $log->updateLastActivity();

            $user->authentications()->save($log);
        }
    }
}

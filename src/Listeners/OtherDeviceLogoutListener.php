<?php

namespace Rappasoft\LaravelAuthenticationLog\Listeners;

use Illuminate\Auth\Events\OtherDeviceLogout;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Rappasoft\LaravelAuthenticationLog\Helpers\DeviceFingerprint;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;
use Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable;

class OtherDeviceLogoutListener
{
    public Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function handle(OtherDeviceLogout $event): void
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
            $authenticationLog = $user->authentications()->fromDevice($deviceId)->first();
            
            if (! $authenticationLog) {
                $authenticationLog = $user->authentications()->whereIpAddress($ip)->whereUserAgent($userAgent)->first();
            }

            if (! $authenticationLog) {
                $authenticationLog = new AuthenticationLog([
                    'ip_address' => $ip,
                    'user_agent' => $userAgent,
                    'device_id' => $deviceId,
                ]);
            }

            // Clear all other active sessions
            foreach ($user->authentications()->whereLoginSuccessful(true)->whereNull('logout_at')->get() as $log) {
                if ($log->id !== $authenticationLog->id) {
                    $log->update([
                        'cleared_by_user' => true,
                        'logout_at' => now(),
                    ]);
                }
            }
        }
    }
}

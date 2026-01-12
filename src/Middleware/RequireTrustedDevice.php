<?php

namespace Rappasoft\LaravelAuthenticationLog\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTrustedDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            abort(401, 'Unauthenticated');
        }

        $user = $request->user();

        if (! method_exists($user, 'authentications')) {
            return $next($request);
        }

        $deviceId = \Rappasoft\LaravelAuthenticationLog\Helpers\DeviceFingerprint::generate($request);

        if (! $user->isDeviceTrusted($deviceId)) {
            abort(403, 'This action requires a trusted device. Please verify your device in your account settings.');
        }

        return $next($request);
    }
}

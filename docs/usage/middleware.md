---
title: Middleware
weight: 6
---

The package includes middleware to protect routes that require trusted devices.

## Require Trusted Device Middleware

Protect sensitive routes by requiring users to be logged in from a trusted device:

```php
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'device.trusted'])->group(function () {
    Route::get('/settings/security', [SettingsController::class, 'security']);
    Route::post('/settings/change-password', [SettingsController::class, 'changePassword']);
    Route::get('/billing', [BillingController::class, 'index']);
});
```

## How It Works

The middleware:
1. Checks if the user is authenticated
2. Generates a fingerprint for the current device
3. Verifies the device is marked as trusted
4. Returns 403 error if device is not trusted

## Error Response

If a user tries to access a protected route from an untrusted device, they'll receive a 403 error with the message:

> "This action requires a trusted device. Please verify your device in your account settings."

## Example: Trust Device Flow

```php
// Route to trust current device
Route::post('/devices/trust-current', function () {
    $user = auth()->user();
    $deviceId = \Rappasoft\LaravelAuthenticationLog\Helpers\DeviceFingerprint::generate(request());
    
    $user->trustDevice($deviceId);
    
    return redirect()->back()->with('success', 'Device trusted successfully');
})->name('devices.trust-current');

// Protected route
Route::middleware(['auth', 'device.trusted'])->group(function () {
    Route::get('/sensitive-action', function () {
        return view('sensitive-action');
    });
});
```

## Customizing Middleware Behavior

You can create your own middleware based on the package middleware:

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Rappasoft\LaravelAuthenticationLog\Helpers\DeviceFingerprint;

class RequireTrustedDeviceOrVerification
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $deviceId = DeviceFingerprint::generate($request);
        
        // Allow if device is trusted OR user has verified via 2FA
        if ($user->isDeviceTrusted($deviceId) || $request->session()->has('2fa_verified')) {
            return $next($request);
        }
        
        // Redirect to verification page instead of 403
        return redirect()->route('verify-device');
    }
}
```


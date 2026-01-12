---
title: Configuration
weight: 2
---

## Publishing Assets

### New Installations

You can publish and run the migrations with:

```bash
php artisan vendor:publish --provider="Rappasoft\LaravelAuthenticationLog\LaravelAuthenticationLogServiceProvider" --tag="authentication-log-migrations"
php artisan migrate
```

### Upgrading from v3.x or Earlier

If you're upgrading from an older version, the package includes an upgrade migration that will safely add new columns to your existing `authentication_log` table:

```bash
# Update the package
composer update rappasoft/laravel-authentication-log

# Publish migrations (includes upgrade migration)
php artisan vendor:publish --provider="Rappasoft\LaravelAuthenticationLog\LaravelAuthenticationLogServiceProvider" --tag="authentication-log-migrations"

# Run migrations (upgrade migration checks for existing columns)
php artisan migrate
```

The upgrade migration (`*_add_new_features_to_authentication_log_table.php`) will:
- Check if each new column already exists
- Only add columns that don't exist
- Preserve all existing data
- Set safe default values for new columns

**Note:** Existing authentication logs will have `null` values for new columns (`device_id`, `device_name`, etc.). Only new logs created after the upgrade will populate these fields.

For detailed upgrade instructions, see the [Upgrade Guide](/docs/laravel-authentication-log/start/upgrade).

You can publish the view/email files with:
```bash
php artisan vendor:publish --provider="Rappasoft\LaravelAuthenticationLog\LaravelAuthenticationLogServiceProvider" --tag="authentication-log-views"
```

You can publish the config file with:
```bash
php artisan vendor:publish --provider="Rappasoft\LaravelAuthenticationLog\LaravelAuthenticationLogServiceProvider" --tag="authentication-log-config"
```

This is the contents of the published config file:

```php
return [
    // The database table name
    // You can change this if the database keys get too long for your driver
    'table_name' => 'authentication_log',

    // The database connection where the authentication_log table resides. Leave empty to use the default
    'db_connection' => null,

    // The events the package listens for to log
    'events' => [
        'login' => \Illuminate\Auth\Events\Login::class,
        'failed' => \Illuminate\Auth\Events\Failed::class,
        'logout' => \Illuminate\Auth\Events\Logout::class,
        'other-device-logout' => \Illuminate\Auth\Events\OtherDeviceLogout::class,
    ],

    'listeners' => [
        'login' => \Rappasoft\LaravelAuthenticationLog\Listeners\LoginListener::class,
        'failed' => \Rappasoft\LaravelAuthenticationLog\Listeners\FailedLoginListener::class,
        'logout' => \Rappasoft\LaravelAuthenticationLog\Listeners\LogoutListener::class,
        'other-device-logout' => \Rappasoft\LaravelAuthenticationLog\Listeners\OtherDeviceLogoutListener::class,
    ],

    'notifications' => [
        'new-device' => [
            // Send the NewDevice notification
            'enabled' => env('NEW_DEVICE_NOTIFICATION', true),

            // Use torann/geoip to attempt to get a location
            'location' => true,

            // The Notification class to send
            'template' => \Rappasoft\LaravelAuthenticationLog\Notifications\NewDevice::class,

            // Rate limiting for notifications (max attempts per time period)
            'rate_limit' => env('NEW_DEVICE_NOTIFICATION_RATE_LIMIT', 3),
            'rate_limit_decay' => env('NEW_DEVICE_NOTIFICATION_RATE_LIMIT_DECAY', 60), // minutes
        ],
        'failed-login' => [
            // Send the FailedLogin notification
            'enabled' => env('FAILED_LOGIN_NOTIFICATION', false),

            // Use torann/geoip to attempt to get a location
            'location' => true,

            // The Notification class to send
            'template' => \Rappasoft\LaravelAuthenticationLog\Notifications\FailedLogin::class,

            // Rate limiting for notifications (max attempts per time period)
            'rate_limit' => env('FAILED_LOGIN_NOTIFICATION_RATE_LIMIT', 5),
            'rate_limit_decay' => env('FAILED_LOGIN_NOTIFICATION_RATE_LIMIT_DECAY', 60), // minutes
        ],
        'suspicious-activity' => [
            // Send the SuspiciousActivity notification (disabled by default)
            'enabled' => env('SUSPICIOUS_ACTIVITY_NOTIFICATION', false),

            // Use torann/geoip to attempt to get a location
            'location' => true,

            // The Notification class to send
            'template' => \Rappasoft\LaravelAuthenticationLog\Notifications\SuspiciousActivity::class,

            // Rate limiting for notifications (max attempts per time period)
            'rate_limit' => env('SUSPICIOUS_ACTIVITY_NOTIFICATION_RATE_LIMIT', 3),
            'rate_limit_decay' => env('SUSPICIOUS_ACTIVITY_NOTIFICATION_RATE_LIMIT_DECAY', 60), // minutes
        ],
    ],

    // Suspicious activity detection
    'suspicious' => [
        // Threshold for failed login attempts to be considered suspicious
        'failed_login_threshold' => env('AUTH_LOG_SUSPICIOUS_FAILED_THRESHOLD', 5),

        // Check for unusual login times
        'check_unusual_times' => env('AUTH_LOG_CHECK_UNUSUAL_TIMES', false),

        // Usual login hours (0-23)
        'usual_hours' => [9, 10, 11, 12, 13, 14, 15, 16, 17],
    ],

    // Webhook configuration
    'webhooks' => [
        // [
        //     'url' => 'https://example.com/webhook',
        //     'events' => ['login', 'failed', 'new_device', 'suspicious'],
        //     'headers' => [
        //         'Authorization' => 'Bearer your-token',
        //     ],
        // ],
    ],

    // Webhook settings
    'webhook_settings' => [
        'log_failures' => env('AUTH_LOG_WEBHOOK_LOG_FAILURES', true),
        'timeout' => env('AUTH_LOG_WEBHOOK_TIMEOUT', 10),
    ],

    // When the clean-up command is run, delete old logs greater than `purge` days
    // Don't schedule the clean-up command if you want to keep logs forever.
    'purge' => 365,

    // Prevent session restorations from being logged as new logins
    // When Laravel restores a session (e.g., page refresh, remember me cookie), 
    // it fires the Login event. This setting prevents those from creating duplicate log entries.
    'prevent_session_restoration_logging' => env('AUTH_LOG_PREVENT_SESSION_RESTORATION', true),
    
    // Time window (in minutes) to consider a login as a session restoration
    // If an active session exists for the same device within this window, update it instead of creating a new entry
    'session_restoration_window_minutes' => env('AUTH_LOG_SESSION_RESTORATION_WINDOW', 5),

    // If you are behind an CDN proxy, set 'behind_cdn.http_header_field' to the corresponding http header field of your cdn
    // For cloudflare you can have look at: https://developers.cloudflare.com/fundamentals/get-started/reference/http-request-headers/
    // 'behind_cdn' => [
    //     'http_header_field' => 'HTTP_CF_CONNECTING_IP' // used by Cloudflare
    // ],

    // If you are not a cdn user, use false
    'behind_cdn' => false,
];
```

If you installed `torann/geoip` you should also publish that config file to set your defaults:

```
php artisan vendor:publish --provider="Torann\GeoIP\GeoIPServiceProvider" --tag=config
```

## Setting up your model

You must add the `AuthenticationLoggable` and `Notifiable` traits to the models you want to track.

```php
use Illuminate\Notifications\Notifiable;
use Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable, AuthenticationLoggable;
}
```

The package will listen for Laravel's Login, Logout, Failed, and OtherDeviceLogout events.

## Suspicious Activity Detection

Configure suspicious activity detection thresholds:

```php
'suspicious' => [
    'failed_login_threshold' => 5, // Number of failed logins in 1 hour to trigger suspicious flag
    'check_unusual_times' => true, // Enable unusual time detection
    'usual_hours' => [9, 10, 11, 12, 13, 14, 15, 16, 17], // Hours considered "usual"
],
```

### Suspicious Activity Notifications

You can enable notifications when suspicious activity is detected. **This is disabled by default.**

```php
'notifications' => [
    'suspicious-activity' => [
        'enabled' => env('SUSPICIOUS_ACTIVITY_NOTIFICATION', false),
        'location' => function_exists('geoip'),
        'template' => \Rappasoft\LaravelAuthenticationLog\Notifications\SuspiciousActivity::class,
        'rate_limit' => env('SUSPICIOUS_ACTIVITY_NOTIFICATION_RATE_LIMIT', 3),
        'rate_limit_decay' => env('SUSPICIOUS_ACTIVITY_NOTIFICATION_RATE_LIMIT_DECAY', 60),
    ],
],
```

When enabled, users will receive notifications for:
- Multiple failed login attempts
- Rapid location changes
- Unusual login times (if enabled)

See the [Suspicious Activity documentation](/docs/laravel-authentication-log/usage/suspicious-activity) for more details.

## Webhook Configuration

Set up webhooks to receive authentication events:

```php
'webhooks' => [
    [
        'url' => 'https://example.com/webhook',
        'events' => ['login', 'failed', 'new_device', 'suspicious'], // or ['*'] for all events
        'headers' => [
            'Authorization' => 'Bearer your-token',
        ],
    ],
],
```

Available events: `login`, `failed`, `new_device`, `suspicious`

## Session Restoration Prevention

**As of v4.0.0**, the package automatically prevents session restorations (page refreshes, remember me cookie restorations) from creating duplicate log entries. When Laravel fires the `Login` event during session restoration, the package detects this and updates the `last_activity_at` timestamp instead of creating a new log entry.

### Configuration

You can configure this behavior in your `config/authentication-log.php`:

```php
// Prevent session restorations from being logged as new logins
// When Laravel restores a session (e.g., page refresh, remember me cookie), 
// it fires the Login event. This setting prevents those from creating duplicate log entries.
'prevent_session_restoration_logging' => env('AUTH_LOG_PREVENT_SESSION_RESTORATION', true),

// Time window (in minutes) to consider a login as a session restoration
// If an active session exists for the same device within this window, update it instead of creating a new entry
'session_restoration_window_minutes' => env('AUTH_LOG_SESSION_RESTORATION_WINDOW', 5),
```

**Default behavior:**
- `prevent_session_restoration_logging`: `true` (enabled by default)
- `session_restoration_window_minutes`: `5` minutes

If an active session exists for the same device/user within the configured window, the package will:
- Update `last_activity_at` on the existing log entry
- Skip creating a new log entry
- Skip sending notifications

This prevents duplicate log entries from page refreshes while still logging actual new logins from different devices or after logout.

### Disabling Session Restoration Prevention

If you want to log every Login event (including session restorations), you can disable this feature:

```php
'prevent_session_restoration_logging' => false,
```

## Overriding default Laravel events

If you would like to listen to your own events you may override them in the package config (as of v1.2).

> **Note:** As of v4.0.0, the session restoration prevention feature handles this automatically. The event override workaround below is no longer necessary but still available if you prefer custom event handling.

### Example event override (Legacy Workaround)

You may notice that Laravel [fires a Login event when the session renews](https://github.com/laravel/framework/blob/master/src/Illuminate/Auth/SessionGuard.php#L149) if the user clicked 'remember me' when logging in. **This is now handled automatically by the session restoration prevention feature**, but if you prefer custom event handling, you can fire your own `Login` event instead of listening for Laravel's.

You can create a Login event that takes the user:

```php
<?php

namespace App\Domains\Auth\Events;

use Illuminate\Queue\SerializesModels;

class Login
{
    use SerializesModels;

    public $user;

    public function __construct($user)
    {
        $this->user = $user;
    }
}
```

Then override it in the package config:

```php
// The events the package listens for to log
'events' => [
    'login' => \App\Domains\Auth\Events\Login::class,
    ...
],
```

Then call it where you login your user:

```php
event(new Login($user));
```

Now the package will only register actual login events, and not session re-authentications.

### Overriding in Fortify

If you are working with Fortify and would like to register your own Login event, you can append a class to the authentication stack:

In FortifyServiceProvider:

```php
Fortify::authenticateThrough(function () {
    return array_filter([
        ...
        FireLoginEvent::class,
    ]);
});
```

`FireLoginEvent` is just a class that fires the event:

```php
<?php

namespace App\Domains\Auth\Actions;

use App\Domains\Auth\Events\Login;

class FireLoginEvent
{
    public function handle($request, $next)
    {
        if ($request->user()) {
            event(new Login($request->user()));
        }

        return $next($request);
    }
}
```

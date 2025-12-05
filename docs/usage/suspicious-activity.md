---
title: Suspicious Activity Detection
weight: 5
---

The package automatically detects suspicious authentication patterns and flags them for review.

## Automatic Detection

Suspicious activity is automatically detected during login and failed login events. When detected, the authentication log is marked with `is_suspicious = true` and includes a reason.

## Notifications

You can enable email, Slack, or SMS notifications when suspicious activity is detected. **This feature is disabled by default.**

### Enabling Suspicious Activity Notifications

Add the following to your `.env` file:

```env
SUSPICIOUS_ACTIVITY_NOTIFICATION=true
SUSPICIOUS_ACTIVITY_NOTIFICATION_RATE_LIMIT=3
SUSPICIOUS_ACTIVITY_NOTIFICATION_RATE_LIMIT_DECAY=60
```

Or configure it directly in `config/authentication-log.php`:

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

When enabled, users will receive notifications for all types of suspicious activity:
- Multiple failed login attempts
- Rapid location changes
- Unusual login times (if enabled)

The notification includes details about the suspicious activity, login time, IP address, browser, and location (if available).

## Detection Rules

### Multiple Failed Logins

Detects when a user has multiple failed login attempts within a short time period:

```php
'suspicious' => [
    'failed_login_threshold' => 5, // 5 failed logins in 1 hour triggers suspicious flag
],
```

### Rapid Location Changes

Detects when logins occur from multiple countries within a short time period (e.g., login from US, then UK within an hour).

### Unusual Login Times

Detects logins outside of normal business hours (if enabled):

```php
'suspicious' => [
    'check_unusual_times' => true,
    'usual_hours' => [9, 10, 11, 12, 13, 14, 15, 16, 17], // 9 AM to 5 PM
],
```

## Manual Detection

You can manually check for suspicious activity:

```php
$user = User::find(1);
$suspiciousActivities = $user->detectSuspiciousActivity();

// Returns array of suspicious activities:
// [
//     [
//         'type' => 'multiple_failed_logins',
//         'count' => 5,
//         'message' => '5 failed login attempts in the last hour'
//     ],
//     [
//         'type' => 'rapid_location_change',
//         'countries' => ['US', 'UK'],
//         'message' => 'Login from multiple countries within an hour'
//     ],
//     [
//         'type' => 'unusual_login_time',
//         'hour' => 3,
//         'message' => 'Login at unusual time: 3:00'
//     ]
// ]
```

## Marking Logs as Suspicious

Manually mark a log as suspicious:

```php
$log = AuthenticationLog::find(1);
$log->markAsSuspicious('Manual review: Unusual pattern detected');
```

## Querying Suspicious Logs

```php
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;

// Get all suspicious logs
$suspiciousLogs = AuthenticationLog::suspicious()->get();

// Get suspicious logs for a user
$userSuspiciousLogs = AuthenticationLog::forUser($user)->suspicious()->get();

// Get recent suspicious activities
$recentSuspicious = AuthenticationLog::suspicious()->recent(7)->get();
```

## Example: Suspicious Activity Alert

```php
// In your LoginController or similar
public function login(Request $request)
{
    // ... authentication logic ...
    
    $user = auth()->user();
    $suspicious = $user->detectSuspiciousActivity();
    
    if (!empty($suspicious)) {
        // Log suspicious activity
        \Log::warning('Suspicious activity detected', [
            'user_id' => $user->id,
            'activities' => $suspicious,
        ]);
        
        // Optionally require additional verification
        // return redirect()->route('verify-suspicious-login');
    }
    
    return redirect()->intended();
}
```

## Getting Suspicious Activity Count

```php
$user = User::find(1);
$suspiciousCount = $user->getSuspiciousActivitiesCount();
```


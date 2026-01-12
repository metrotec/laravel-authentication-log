---
title: Getting Logs
weight: 1
---

## Basic Log Retrieval

Get all authentication logs for the user:
```php
User::find(1)->authentications;
```

Get the latest authentication:
```php
User::find(1)->latestAuthentication;
```

## Login Information Methods

Get the user's last login information:
```php
User::find(1)->lastLoginAt();

User::find(1)->lastSuccessfulLoginAt();

User::find(1)->lastLoginIp();

User::find(1)->lastSuccessfulLoginIp();
```

Get the user's previous login time & IP address (ignoring the current login):
```php
auth()->user()->previousLoginAt();

auth()->user()->previousLoginIp();
```

## Query Scopes

The `AuthenticationLog` model provides powerful query scopes for filtering:

```php
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;

// Filter successful logins
$successfulLogins = AuthenticationLog::successful()->get();

// Filter failed logins
$failedLogins = AuthenticationLog::failed()->get();

// Filter by IP address
$ipLogs = AuthenticationLog::fromIp('192.168.1.1')->get();

// Filter recent logs (last 7 days by default)
$recentLogs = AuthenticationLog::recent(7)->get();

// Filter suspicious activities
$suspicious = AuthenticationLog::suspicious()->get();

// Filter active sessions
$activeSessions = AuthenticationLog::active()->get();

// Filter trusted devices
$trustedDevices = AuthenticationLog::trusted()->get();

// Filter by device ID
$deviceLogs = AuthenticationLog::fromDevice($deviceId)->get();

// Filter for specific user
$userLogs = AuthenticationLog::forUser($user)->get();

// Chain multiple scopes
$recentSuspicious = AuthenticationLog::recent(30)->suspicious()->get();
```

## Statistics

Get comprehensive login statistics:

```php
$user = User::find(1);

// Get all statistics
$stats = $user->getLoginStats();
// Returns:
// [
//     'total_logins' => 150,
//     'failed_attempts' => 5,
//     'unique_devices' => 3,
//     'unique_ips' => 8,
//     'last_30_days' => 45,
//     'last_7_days' => 12,
//     'suspicious_activities' => 2,
//     'trusted_devices' => 2,
// ]

// Or get individual stats
$totalLogins = $user->getTotalLogins();
$failedAttempts = $user->getFailedAttempts();
$uniqueDevices = $user->getUniqueDevicesCount();
$suspiciousCount = $user->getSuspiciousActivitiesCount();
```

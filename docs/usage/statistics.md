---
title: Statistics and Insights
weight: 8
---

The package provides comprehensive statistics and insights about user authentication patterns.

## Getting Statistics

Get all statistics for a user:

```php
$user = User::find(1);
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
```

## Individual Statistics

Get specific statistics:

```php
$user = User::find(1);

// Total successful logins
$totalLogins = $user->getTotalLogins();

// Total failed login attempts
$failedAttempts = $user->getFailedAttempts();

// Number of unique devices
$uniqueDevices = $user->getUniqueDevicesCount();

// Number of suspicious activities
$suspiciousCount = $user->getSuspiciousActivitiesCount();
```

## Example: Statistics Dashboard

Here's an example of displaying statistics in a dashboard:

```php
// In your controller
public function dashboard()
{
    $user = auth()->user();
    $stats = $user->getLoginStats();
    
    return view('dashboard', compact('stats'));
}

// In your view
<div class="stats-grid">
    <div class="stat-card">
        <h3>Total Logins</h3>
        <p class="stat-value">{{ $stats['total_logins'] }}</p>
    </div>
    
    <div class="stat-card">
        <h3>Failed Attempts</h3>
        <p class="stat-value">{{ $stats['failed_attempts'] }}</p>
    </div>
    
    <div class="stat-card">
        <h3>Unique Devices</h3>
        <p class="stat-value">{{ $stats['unique_devices'] }}</p>
    </div>
    
    <div class="stat-card">
        <h3>Last 30 Days</h3>
        <p class="stat-value">{{ $stats['last_30_days'] }}</p>
    </div>
    
    @if($stats['suspicious_activities'] > 0)
        <div class="stat-card alert">
            <h3>Suspicious Activities</h3>
            <p class="stat-value">{{ $stats['suspicious_activities'] }}</p>
            <a href="{{ route('security.review') }}">Review</a>
        </div>
    @endif
</div>
```

## Combining with Query Scopes

You can combine statistics with query scopes for more detailed insights:

```php
$user = User::find(1);

// Get statistics for last 7 days only
$recentLogs = $user->authentications()->recent(7)->get();
$recentStats = [
    'total' => $recentLogs->where('login_successful', true)->count(),
    'failed' => $recentLogs->where('login_successful', false)->count(),
    'suspicious' => $recentLogs->where('is_suspicious', true)->count(),
];

// Get statistics by device
$deviceStats = $user->authentications()
    ->successful()
    ->select('device_id', 'device_name', \DB::raw('count(*) as login_count'))
    ->groupBy('device_id', 'device_name')
    ->get();
```


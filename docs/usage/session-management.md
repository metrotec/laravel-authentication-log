---
title: Session Management
weight: 3
---

The package provides comprehensive session management capabilities, allowing you to view and manage active user sessions.

## Getting Active Sessions

Get all active sessions for a user:

```php
$user = User::find(1);

// Get active sessions collection
$activeSessions = $user->getActiveSessions();

// Get count of active sessions
$sessionCount = $user->getActiveSessionsCount();

// Get active sessions query builder (for further filtering)
$activeSessionsQuery = $user->activeSessions();
```

## Revoking Sessions

### Revoke a Specific Session

```php
$user = User::find(1);
$sessionId = 123;

if ($user->revokeSession($sessionId)) {
    // Session revoked successfully
}
```

### Revoke All Other Sessions

Keep the current device logged in while logging out all other devices:

```php
$user = User::find(1);
$currentDeviceId = DeviceFingerprint::generate(request());

$revokedCount = $user->revokeAllOtherSessions($currentDeviceId);
// Returns the number of sessions revoked
```

### Revoke All Sessions

Log out the user from all devices:

```php
$user = User::find(1);

$revokedCount = $user->revokeAllSessions();
// Returns the number of sessions revoked
```

## Checking Session Status

Check if a log entry represents an active session:

```php
$log = AuthenticationLog::find(1);

if ($log->isActive()) {
    // Session is currently active
}
```

## Example: Session Management UI

Here's an example of how you might display active sessions to users:

```php
// In your controller
public function sessions()
{
    $user = auth()->user();
    $activeSessions = $user->getActiveSessions();
    
    return view('profile.sessions', compact('activeSessions'));
}

// In your view
@foreach($activeSessions as $session)
    <div class="session-item">
        <div>
            <strong>{{ $session->device_name ?? 'Unknown Device' }}</strong>
            @if($session->is_trusted)
                <span class="badge badge-success">Trusted</span>
            @endif
        </div>
        <div>
            IP: {{ $session->ip_address }}
        </div>
        <div>
            Last Login: {{ $session->login_at->diffForHumans() }}
        </div>
        <div>
            <form action="{{ route('sessions.revoke', $session->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit">Revoke Session</button>
            </form>
        </div>
    </div>
@endforeach
```

## Example: Revoke Session Route

```php
Route::post('/sessions/{session}/revoke', function ($sessionId) {
    $user = auth()->user();
    
    if ($user->revokeSession($sessionId)) {
        return redirect()->back()->with('success', 'Session revoked successfully');
    }
    
    return redirect()->back()->with('error', 'Failed to revoke session');
})->name('sessions.revoke');
```


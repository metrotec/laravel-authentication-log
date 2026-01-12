---
title: Webhooks
weight: 7
---

The package can send webhooks to external services when authentication events occur.

## Configuration

Configure webhooks in your `config/authentication-log.php`:

```php
'webhooks' => [
    [
        'url' => 'https://example.com/webhook',
        'events' => ['login', 'failed', 'new_device', 'suspicious'],
        'headers' => [
            'Authorization' => 'Bearer your-token',
            'X-Custom-Header' => 'value',
        ],
    ],
    [
        'url' => 'https://another-service.com/webhook',
        'events' => ['*'], // Listen to all events
        'headers' => [
            'Authorization' => 'Bearer another-token',
        ],
    ],
],
```

## Available Events

- `login` - Fired when a user successfully logs in
- `failed` - Fired when a login attempt fails
- `new_device` - Fired when a user logs in from a new device
- `suspicious` - Fired when suspicious activity is detected

Use `['*']` to listen to all events.

## Webhook Payload

Each webhook sends a JSON payload with the following structure:

```json
{
    "event": "login",
    "timestamp": "2024-01-15T10:30:00+00:00",
    "user": {
        "id": 1,
        "email": "user@example.com"
    },
    "authentication_log": {
        "id": 123,
        "ip_address": "192.168.1.1",
        "user_agent": "Mozilla/5.0...",
        "device_id": "abc123...",
        "device_name": "Chrome on Windows",
        "login_at": "2024-01-15T10:30:00+00:00",
        "login_successful": true,
        "is_suspicious": false,
        "location": {
            "city": "New York",
            "state": "NY",
            "country": "US",
            "country_code": "US"
        }
    }
}
```

## Webhook Settings

Configure webhook behavior:

```php
'webhook_settings' => [
    'log_failures' => true, // Log failed webhook requests
    'timeout' => 10, // HTTP timeout in seconds
],
```

## Error Handling

Failed webhook requests are automatically logged (if `log_failures` is enabled). The package will continue processing even if a webhook fails, ensuring authentication logging is not interrupted.

## Example: Webhook Receiver

Here's an example of how you might handle webhooks on the receiving end:

```php
// In your webhook receiver
Route::post('/webhook', function (Request $request) {
    $event = $request->input('event');
    $user = $request->input('user');
    $log = $request->input('authentication_log');
    
    switch ($event) {
        case 'suspicious':
            // Send alert to security team
            SecurityAlert::create([
                'user_id' => $user['id'],
                'type' => 'suspicious_activity',
                'details' => $log,
            ]);
            break;
            
        case 'new_device':
            // Log new device for audit
            AuditLog::create([
                'user_id' => $user['id'],
                'action' => 'new_device_login',
                'details' => $log,
            ]);
            break;
    }
    
    return response()->json(['status' => 'received']);
})->middleware('auth:sanctum'); // Protect your webhook endpoint
```

## Testing Webhooks

You can test webhooks locally using tools like [ngrok](https://ngrok.com/) or [webhook.site](https://webhook.site/):

1. Set up a public URL using ngrok or webhook.site
2. Add it to your webhook configuration
3. Trigger authentication events
4. Check the webhook receiver for payloads


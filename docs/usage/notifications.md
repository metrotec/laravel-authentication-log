---
title: Notifications
weight: 2
---

Notifications may be sent on the `mail`, `vonage` (formerly Nexmo), and `slack` channels but by **default notify via email**.

You may define a `notifyAuthenticationLogVia` method on your authenticatable models to determine which channels the notification should be delivered on:

```php
public function notifyAuthenticationLogVia()
{
    return ['vonage', 'mail', 'slack'];
}
```

You must install the [Slack](https://laravel.com/docs/notifications#routing-slack-notifications) and [Vonage](https://laravel.com/docs/notifications#routing-sms-notifications) drivers to use those routes and follow their documentation on setting it up for your specific authenticatable models.

## New Device Notifications

Enabled by default, they use the `\Rappasoft\LaravelAuthenticationLog\Notifications\NewDevice` class which can be overridden in the config file.

### Rate Limiting

New device notifications are rate-limited by default to prevent spam. You can configure this in the config file:

```php
'new-device' => [
    'rate_limit' => 3, // Maximum 3 notifications per time period
    'rate_limit_decay' => 60, // Time period in minutes
],
```

This means a user will receive a maximum of 3 new device notifications per hour. Additional logins from new devices within that time period will not trigger notifications.

## Failed Login Notifications

Disabled by default, they use the `\Rappasoft\LaravelAuthenticationLog\Notifications\FailedLogin` class which can be overridden in the config file.

### Rate Limiting

Failed login notifications also support rate limiting:

```php
'failed-login' => [
    'rate_limit' => 5, // Maximum 5 notifications per time period
    'rate_limit_decay' => 60, // Time period in minutes
],
```

## Location

If the `torann/geoip` package is installed, it will attempt to include location information to the notifications by default.

You can turn this off within the configuration for each template.

**Note:** By default when working locally, no location will be recorded because it will send back the `default address` from the `geoip` config file. You can override this behavior in the email templates.

## Custom Notification Templates

You can override the notification classes in the config file:

```php
'notifications' => [
    'new-device' => [
        'template' => \App\Notifications\CustomNewDevice::class,
    ],
    'failed-login' => [
        'template' => \App\Notifications\CustomFailedLogin::class,
    ],
],
```

Your custom notification classes should extend the base notification classes or implement the same interface.

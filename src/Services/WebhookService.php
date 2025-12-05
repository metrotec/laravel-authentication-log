<?php

namespace Rappasoft\LaravelAuthenticationLog\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;

class WebhookService
{
    public static function send(string $event, AuthenticationLog $log, $user): void
    {
        $webhooks = config('authentication-log.webhooks', []);

        if (empty($webhooks)) {
            return;
        }

        $payload = [
            'event' => $event,
            'timestamp' => now()->toIso8601String(),
            'user' => [
                'id' => $user->id,
                'email' => $user->email ?? null,
            ],
            'authentication_log' => [
                'id' => $log->id,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'device_id' => $log->device_id,
                'device_name' => $log->device_name,
                'login_at' => $log->login_at?->toIso8601String(),
                'login_successful' => $log->login_successful,
                'is_suspicious' => $log->is_suspicious,
                'location' => $log->location,
            ],
        ];

        foreach ($webhooks as $webhook) {
            if (!isset($webhook['url']) || !isset($webhook['events'])) {
                continue;
            }

            if (!in_array($event, $webhook['events']) && !in_array('*', $webhook['events'])) {
                continue;
            }

            try {
                $timeout = config('authentication-log.webhook_settings.timeout', 10);
                $response = Http::timeout($timeout)
                    ->withHeaders($webhook['headers'] ?? [])
                    ->post($webhook['url'], $payload);

                if (! $response->successful() && config('authentication-log.webhook_settings.log_failures', true)) {
                    Log::warning('Webhook failed', [
                        'url' => $webhook['url'],
                        'event' => $event,
                        'status' => $response->status(),
                    ]);
                }
            } catch (\Exception $e) {
                if (config('authentication-log.webhook_settings.log_failures', true)) {
                    Log::error('Webhook exception', [
                        'url' => $webhook['url'],
                        'event' => $event,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}


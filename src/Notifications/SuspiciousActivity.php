<?php

namespace Rappasoft\LaravelAuthenticationLog\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Messages\VonageMessage;
use Illuminate\Notifications\Notification;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;

class SuspiciousActivity extends Notification implements ShouldQueue
{
    use Queueable;

    public AuthenticationLog $authenticationLog;
    public array $suspiciousActivities;

    public function __construct(AuthenticationLog $authenticationLog, array $suspiciousActivities = [])
    {
        $this->authenticationLog = $authenticationLog;
        $this->suspiciousActivities = $suspiciousActivities;
    }

    public function via($notifiable)
    {
        return $notifiable->notifyAuthenticationLogVia();
    }

    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->subject(__('Suspicious activity detected on your :app account', ['app' => config('app.name')]))
            ->markdown('authentication-log::emails.suspicious', [
                'account' => $notifiable,
                'time' => $this->authenticationLog->login_at,
                'ipAddress' => $this->authenticationLog->ip_address,
                'browser' => $this->authenticationLog->user_agent,
                'location' => $this->authenticationLog->location,
                'suspiciousActivities' => $this->suspiciousActivities,
            ]);
    }

    public function toSlack($notifiable)
    {
        $messages = collect($this->suspiciousActivities)->pluck('message')->implode(', ');

        return (new SlackMessage())
            ->from(config('app.name'))
            ->error()
            ->content(__('Suspicious activity detected on your :app account', ['app' => config('app.name')]))
            ->attachment(function ($attachment) use ($notifiable, $messages) {
                $attachment->fields([
                    __('Account') => $notifiable->email,
                    __('Time') => $this->authenticationLog->login_at->toDateTimeString(),
                    __('IP Address') => $this->authenticationLog->ip_address,
                    __('Browser') => $this->authenticationLog->user_agent,
                    __('Location') =>
                        $this->authenticationLog->location &&
                        $this->authenticationLog->location['default'] === false ?
                            ($this->authenticationLog->location['city'] ?? 'N/A') . ', ' . ($this->authenticationLog->location['state'] ?? 'N/A') :
                            'Unknown',
                    __('Suspicious Activity') => $messages,
                ]);
            });
    }

    public function toVonage($notifiable)
    {
        $messages = collect($this->suspiciousActivities)->pluck('message')->implode(', ');

        return (new VonageMessage())
            ->content(__('Suspicious activity detected on your :app account: :activity', [
                'app' => config('app.name'),
                'activity' => $messages,
            ]));
    }
}

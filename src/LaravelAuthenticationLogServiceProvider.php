<?php

namespace Rappasoft\LaravelAuthenticationLog;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\OtherDeviceLogout;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Route;
use Rappasoft\LaravelAuthenticationLog\Commands\ExportAuthenticationLogsCommand;
use Rappasoft\LaravelAuthenticationLog\Commands\PurgeAuthenticationLogCommand;
use Rappasoft\LaravelAuthenticationLog\Listeners\FailedLoginListener;
use Rappasoft\LaravelAuthenticationLog\Listeners\LoginListener;
use Rappasoft\LaravelAuthenticationLog\Listeners\LogoutListener;
use Rappasoft\LaravelAuthenticationLog\Listeners\OtherDeviceLogoutListener;
use Rappasoft\LaravelAuthenticationLog\Middleware\RequireTrustedDevice;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelAuthenticationLogServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-authentication-log')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasViews()
            ->hasMigration('create_authentication_log_table')
            ->hasMigration('add_new_features_to_authentication_log_table')
            ->hasCommand(PurgeAuthenticationLogCommand::class)
            ->hasCommand(ExportAuthenticationLogsCommand::class);

        $events = $this->app->make(Dispatcher::class);
        $events->listen(config('authentication-log.events.login', Login::class), config('authentication-log.listeners.login', LoginListener::class));
        $events->listen(config('authentication-log.events.failed', Failed::class), config('authentication-log.listeners.failed', FailedLoginListener::class));
        $events->listen(config('authentication-log.events.logout', Logout::class), config('authentication-log.listeners.logout', LogoutListener::class));
        $events->listen(config('authentication-log.events.other-device-logout', OtherDeviceLogout::class), config('authentication-log.listeners.other-device-logout', OtherDeviceLogoutListener::class));
    }

    public function boot(): void
    {
        parent::boot();

        // Register middleware alias
        $this->app['router']->aliasMiddleware('device.trusted', RequireTrustedDevice::class);
    }
}

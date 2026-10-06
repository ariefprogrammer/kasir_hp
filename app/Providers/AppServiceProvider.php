<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\AuditLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Login::class, function (Login $event) {
            AuditLog::record('login', null, null, null, $event->user->email, $event->user->getAuthIdentifier());
        });

        Event::listen(Logout::class, function (Logout $event) {
            if ($event->user) {
                AuditLog::record('logout', null, null, null, $event->user->email, $event->user->getAuthIdentifier());
            }
        });

        Event::listen(Failed::class, function (Failed $event) {
            AuditLog::record(
                'login_failed',
                null,
                null,
                null,
                'Email dicoba: ' . ($event->credentials['email'] ?? '-'),
                $event->user?->getAuthIdentifier(),
            );
        });
    }
}

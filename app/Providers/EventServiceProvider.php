<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\CompteCreated;
use App\Events\TransactionEffectuee;
use App\Listeners\SendClientNotification;
use App\Listeners\SendTransactionNotification;
use App\Models\Compte;
use App\Observers\CompteObserver;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        CompteCreated::class => [
            SendClientNotification::class,
        ],
        TransactionEffectuee::class => [
            SendTransactionNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
      public function boot(): void
    {
        // User::observe(UserObserver::class);
        Compte::observe(CompteObserver::class);
    }
    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

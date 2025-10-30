<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

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
    // public function boot(): void
    // {
    //     \Laravel\Passport\Passport::ignoreRoutes();
    // }
public function boot(): void
{
    $privateKey = env('PASSPORT_PRIVATE_KEY');
    $publicKey  = env('PASSPORT_PUBLIC_KEY');

    if ($privateKey && $publicKey) {
        file_put_contents(storage_path('oauth-private.key'), $privateKey);
        file_put_contents(storage_path('oauth-public.key'), $publicKey);

        Passport::loadKeysFrom(storage_path());
    }
}

}

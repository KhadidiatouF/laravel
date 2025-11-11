<?php

namespace App\Providers;

use App\Http\Repository\ClientRepository;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use App\Http\Interfaces\RepositoriesInterfaces\TransactionRepositoryInterface;
use App\Http\Interfaces\RepositoriesInterfaces\CompteRepositoryInterface;
use App\Http\Repository\TransactionRepository;
use App\Http\Repository\CompteRepo;
use App\Http\Repository\UserRepository;
use App\Http\Services\ClientService;
use App\Http\Services\CompteService;
use App\Http\Services\SmsService;
use App\Http\Services\UserService;
use App\Http\Interfaces\RepositoriesInterfaces\IFirstOrCreateRepository;
use App\Models\Compte;
use App\Observers\CompteObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

        $this->app->singleton(CompteRepositoryInterface::class, function ($app) {  
            return new CompteRepo(new Compte());
        });
        
        $this->app->singleton(CompteService::class, function ($app) {
            return new CompteService($app->make(CompteRepositoryInterface::class), $app->make(UserService::class), $app->make(ClientService::class));
        });

        $this->app->bind(IFirstOrCreateRepository::class, UserRepository::class);

        $this->app->when(UserService::class)->needs(IFirstOrCreateRepository::class)->give(UserRepository::class);

        $this->app->when(ClientService::class)->needs(IFirstOrCreateRepository::class)->give(ClientRepository::class);
        

        $this->app->singleton(UserService::class);
        $this->app->singleton(ClientService::class);
        $this->app->singleton(SmsService::class);
        $this->app->bind(TransactionRepositoryInterface::class, TransactionRepository::class);
        $this->app->bind(CompteRepositoryInterface::class, CompteRepo::class);
        
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
    Compte::observe(CompteObserver::class);

    $privateKey = env('PASSPORT_PRIVATE_KEY');
    $publicKey  = env('PASSPORT_PUBLIC_KEY');

    if ($privateKey && $publicKey) {
        file_put_contents(storage_path('oauth-private.key'), $privateKey);
        file_put_contents(storage_path('oauth-public.key'), $publicKey);

        Passport::loadKeysFrom(storage_path());
    }
}

}

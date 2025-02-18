<?php

namespace App\Providers;

use App\Enums\RoleEnum;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        /**
         * Gates
         */

        // Gate to verify if a user can create an appointment
        Gate::define('create-appointment', function ($user) {
            return $user->role === RoleEnum::Patient->value();
        });

        // Gate to verify if a user can list appointments
        Gate::define('list-appointments', function ($user) {
            return $user->role === RoleEnum::Doctor->value();
        });
    }
}

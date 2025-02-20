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

        // Gate to verify if a user can confirm/reject an appointment
        Gate::define('confirm-cancel-appointment', function ($user, $appointment) {
            // Check whether the user is the doctor of the appointment
            $isDoctorOfAppointment = $appointment->doctor_id === $user->id;
            $hasDoctorRole = $user->role === RoleEnum::Doctor->value();

            return $isDoctorOfAppointment && $hasDoctorRole;
        });

        // Gate to verify if a user can generate a payment link for an appointment
        Gate::define('pay-appointment', function ($user, $appointment) {
            // Check whether the user is the owner of the appointment
            $owner = $appointment->patient_id === $user->id;
            $hasPatientRole = $user->role === RoleEnum::Patient->value();

            return $owner && $hasPatientRole;
        });
    }
}

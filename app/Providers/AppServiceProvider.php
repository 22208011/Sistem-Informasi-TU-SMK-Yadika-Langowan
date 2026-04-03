<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->configureDefaults();
        $this->configureGates();
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }

    /**
     * Configure gates for permission-based authorization
     */
    protected function configureGates(): void
    {
        // Define a gate for each permission dynamically
        Gate::before(function (User $user, string $ability) {
            // Admin has all abilities
            if ($user->isAdmin()) {
                return true;
            }

            // Check if user has the specific permission
            if ($user->hasPermission($ability)) {
                return true;
            }

            return null; // Let other gates handle it
        });
    }
}

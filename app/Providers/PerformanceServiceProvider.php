<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class PerformanceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Disable all strict mode features to prevent MissingAttributeException
        Model::preventLazyLoading(false);
        Model::preventAccessingMissingAttributes(false);
        Model::preventSilentlyDiscardingAttributes(false);

        // Enable query logging in development for slow queries only
        if (! $this->app->isProduction() && config('app.debug')) {
            DB::listen(function ($query) {
                if ($query->time > 500) { // Log very slow queries (> 500ms)
                    logger()->warning('Slow Query Detected', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time.'ms',
                    ]);
                }
            });
        }
    }
}

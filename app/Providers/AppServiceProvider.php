<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passkeys\Passkeys;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Ganti default routes package (session-based) dengan controller Sanctum sendiri.
        Passkeys::ignoreRoutes();
    }
}

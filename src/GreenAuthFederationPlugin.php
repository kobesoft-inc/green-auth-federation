<?php

namespace Green\Auth;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Green\Auth\Http\Controllers\FederationController;
use Illuminate\Support\Facades\Route;

class GreenAuthFederationPlugin implements Plugin
{
    public function getId(): string
    {
        return 'green-auth-idp';
    }

    public function register(Panel $panel): void
    {
        $routes = $panel->getRoutes();
        $panel
            ->routes(function (Panel $panel) use ($routes) {
                if ($routes) {
                    $routes($panel);
                }
                Route::get('/auth/federation/{driver}', [FederationController::class, 'redirect'])
                    ->name('auth.federation-redirect');
                Route::get('/auth/federation/{driver}/callback', [FederationController::class, 'callback'])
                    ->name('auth.federation-callback');
            });
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }
}

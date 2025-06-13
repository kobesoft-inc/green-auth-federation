<?php

namespace Green\Auth;

use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Green\Auth\Services\IdProviderManager;
use Green\Auth\View\FederationButtonRenderer;
use Illuminate\Support\ServiceProvider;

class GreenAuthFederationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('green-auth.federation.id-provider-manager', function () {
            return new IdProviderManager();
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'green-auth-federation');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'green-auth-federation');
        $this->registerRenderHooks();

        // 言語ファイルの公開
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../lang' => $this->app->langPath('vendor/green-auth-federation'),
            ], 'green-auth-federation-lang');
        }
    }

    protected function registerRenderHooks(): void
    {
        // ログインフォーム前にフェデレーション認証ボタンを表示
        FilamentView::registerRenderHook(
            PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
            fn(): string => app(FederationButtonRenderer::class)->renderForCurrentGuard()
        );
    }
}

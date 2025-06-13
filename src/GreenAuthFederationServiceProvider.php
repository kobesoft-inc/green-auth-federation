<?php

namespace Green\Auth;

use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Green\Auth\View\FederationButtonRenderer;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Green認証フェデレーションサービスプロバイダー
 *
 * 認証フェデレーション機能に必要なサービス、ビュー、言語ファイルなどを登録します。
 * また、Filamentパネルのログインフォームにフェデレーション認証ボタンを追加します。
 */
class GreenAuthFederationServiceProvider extends ServiceProvider
{
    /**
     * サービスの登録
     *
     * フェデレーション認証に必要なサービスを登録します。
     *
     * @return void
     */
    public function register(): void
    {
        // IdProviderManagerはGreenAuthFederationPluginで管理されるため、ここでの登録は不要
    }

    /**
     * サービスのブート処理
     *
     * マイグレーション、言語ファイル、ビューファイルの読み込み、
     * レンダーフックの登録、言語ファイルの公開設定、
     * Socialiteドライバーの登録を行います。
     *
     * @return void
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'green-auth-federation');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'green-auth-federation');
        $this->registerRenderHooks();
        $this->registerSocialiteDrivers();

        // 言語ファイルの公開
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../lang' => $this->app->langPath('vendor/green-auth-federation'),
            ], 'green-auth-federation-lang');
        }
    }

    /**
     * レンダーフックの登録
     *
     * Filamentパネルのログインフォームにフェデレーション認証ボタンを表示するための
     * レンダーフックを登録します。
     *
     * @return void
     */
    protected function registerRenderHooks(): void
    {
        // ログインフォーム前にフェデレーション認証ボタンを表示
        FilamentView::registerRenderHook(
            PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
            fn(): string => app(FederationButtonRenderer::class)->renderForCurrentGuard()
        );
    }

    /**
     * Socialiteドライバーの登録
     *
     * フェデレーション認証で使用するSocialiteドライバー（Azure等）を登録します。
     *
     * @return void
     */
    protected function registerSocialiteDrivers(): void
    {
        Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
            $event->extendSocialite('azure', \SocialiteProviders\Azure\Provider::class);
            $event->extendSocialite('google', \SocialiteProviders\Google\Provider::class);
        });
    }
}

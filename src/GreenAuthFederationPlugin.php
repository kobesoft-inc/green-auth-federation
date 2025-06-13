<?php

namespace Green\Auth;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Green\Auth\Concerns\HasIdProviders;
use Green\Auth\Http\Controllers\FederationController;
use Illuminate\Support\Facades\Route;

/**
 * Green認証フェデレーションプラグイン
 *
 * Filamentパネルに認証フェデレーション機能を追加するプラグインです。
 * 外部ID プロバイダー (Google、Azureなど) による認証を可能にします。
 */
class GreenAuthFederationPlugin implements Plugin
{
    use HasIdProviders;

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        // HasIdProvidersトレイトが初期化を担当
    }

    /**
     * プラグインの一意識別子を取得
     *
     * @return string プラグインID
     */
    public function getId(): string
    {
        return 'green-auth-idp';
    }

    /**
     * パネルにプラグインを登録
     *
     * フェデレーション認証用のルートを登録します。
     *
     * @param Panel $panel Filamentパネル
     * @return void
     */
    public function register(Panel $panel): void
    {
        $panel
            ->routes(function (Panel $panel) {
                Route::get('/login/{driver}', [FederationController::class, 'redirect'])
                    ->name('auth.federation-redirect');
                Route::get('/login/{driver}/callback', [FederationController::class, 'callback'])
                    ->name('auth.federation-callback');
            });
    }

    /**
     * プラグインのブート処理
     *
     * @param Panel $panel Filamentパネル
     * @return void
     */
    public function boot(Panel $panel): void
    {
        //
    }

    /**
     * プラグインインスタンスを作成
     *
     * @return static プラグインインスタンス
     */
    public static function make(): static
    {
        return new static();
    }
}

<?php

namespace Green\Auth\View;

use Green\Auth\Facades\IdProviderManager;

/**
 * フェデレーション認証ボタンレンダラー
 * 
 * ログインページでのフェデレーション認証ボタンの表示を担当
 */
class FederationButtonRenderer
{
    /**
     * 指定されたガードのフェデレーション認証ボタンをレンダリング
     *
     * @param string $guard ガード名
     * @return string レンダリングされたHTML
     */
    public function render(string $guard): string
    {
        $providers = IdProviderManager::all($guard);

        if (empty($providers)) {
            return '';
        }

        $actions = [];
        foreach ($providers as $provider) {
            $actions[] = $provider->getLoginAction();
        }

        return view('green-auth-federation::login-buttons', [
            'actions' => $actions
        ])->render();
    }

    /**
     * 現在のガードのフェデレーション認証ボタンをレンダリング
     *
     * @return string レンダリングされたHTML
     */
    public function renderForCurrentGuard(): string
    {
        $guard = filament()->getAuthGuard();
        return $this->render($guard);
    }
}
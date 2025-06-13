<?php

namespace Green\Auth\View;

use Green\Auth\GreenAuthFederationPlugin;

/**
 * フェデレーション認証ボタンレンダラー
 * 
 * ログインページでのフェデレーション認証ボタンの表示を担当。
 * 現在のFilamentパネルのプラグインからIDプロバイダーを取得します。
 */
class FederationButtonRenderer
{
    /**
     * フェデレーション認証ボタンをレンダリング
     * 
     * 現在のパネルのGreenAuthFederationPluginからIDプロバイダーを取得します。
     *
     * @return string レンダリングされたHTML
     */
    public function render(): string
    {
        $providers = $this->getProvidersFromCurrentPanel();

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
     * 既存のメソッド呼び出しとの互換性のため、render()を呼び出します。
     *
     * @return string レンダリングされたHTML
     */
    public function renderForCurrentGuard(): string
    {
        return $this->render();
    }

    /**
     * 現在のパネルのプラグインからIDプロバイダーを取得
     * 
     * @return array<string, \Green\Auth\IdProviders\BaseIdProvider> IDプロバイダー配列
     */
    protected function getProvidersFromCurrentPanel(): array
    {
        $currentPanel = filament()->getCurrentPanel();
        if (!$currentPanel) {
            return [];
        }

        if (!$currentPanel->hasPlugin('green-auth-idp')) {
            return [];
        }

        /** @var GreenAuthFederationPlugin $plugin */
        $plugin = $currentPanel->getPlugin('green-auth-idp');
        return $plugin->getIdProviders();
    }

}
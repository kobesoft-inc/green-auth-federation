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
     * canLoginWithUsernameとcanLoginWithEmailが両方ともfalseの場合、「または」は表示しません。
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

        // ログイン方法をチェック
        $showOrLabel = $this->shouldShowOrLabel();

        return view('green-auth-federation::login-buttons', [
            'actions' => $actions,
            'showOrLabel' => $showOrLabel
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

    /**
     * 「または」ラベルを表示するかどうかを判定
     * 
     * canLoginWithUsernameとcanLoginWithEmailが両方ともfalseの場合はfalseを返す
     * 
     * @return bool 「または」ラベルを表示するかどうか
     */
    protected function shouldShowOrLabel(): bool
    {
        $currentPanel = filament()->getCurrentPanel();
        if (!$currentPanel) {
            return true;
        }

        if (!$currentPanel->hasPlugin('green-auth')) {
            return true;
        }

        $plugin = $currentPanel->getPlugin('green-auth');
        $guardName = $currentPanel->getAuthGuard();
        
        // 設定を取得
        $canLoginWithEmail = config("green-auth.guards.{$guardName}.auth.login_with_email", false);
        $canLoginWithUsername = config("green-auth.guards.{$guardName}.auth.login_with_username", false);
        
        // 両方ともfalseの場合は「または」を表示しない
        return $canLoginWithEmail || $canLoginWithUsername;
    }

}
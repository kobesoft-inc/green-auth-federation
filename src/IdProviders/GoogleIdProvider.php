<?php

namespace Green\Auth\IdProviders;

use Filament\Actions\Action;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\HtmlString;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * Google Cloud Identity認証プロバイダー
 *
 * Google Cloud Identity（G Suite/Google Workspace）での組織認証を提供
 * ドメイン制限やカスタマーIDの指定に対応
 */
class GoogleIdProvider extends BaseIdProvider
{
    protected static ?string $driver = 'google';
    protected ?string $hostedDomain = null;

    /**
     * プロバイダーの新しいインスタンスを作成
     */
    public static function make(): static
    {
        return new static();
    }

    /**
     * ホストドメインを設定
     *
     * @param string $domain 組織のドメイン（例：example.com）
     * @return static
     */
    public function hostedDomain(string $domain): static
    {
        $this->hostedDomain = $domain;
        return $this;
    }


    /**
     * ログインアクションを取得
     */
    public function getLoginAction(): Action
    {
        return Action::make('google_login')
            ->label(__('green-auth-federation::federation.actions.login_with_google_workspace'))
            ->icon($this->getIcon())
            ->url($this->getRedirectUrl())
            ->color('blue');
    }

    /**
     * 認証プロバイダーにリダイレクト
     */
    public function redirect(): RedirectResponse
    {
        $provider = $this->getProvider()
            ->with(['access_type' => 'offline', 'prompt' => 'consent select_account'])
            ->scopes(['openid', 'profile', 'email', ...$this->scopes])
            ->stateless();

        // ホストドメインが設定されている場合、hd パラメータを追加
        if ($this->hostedDomain) {
            $provider->with(['hd' => $this->hostedDomain]);
        }

        return $provider->redirect();
    }

    /**
     * ユーザーデータからアバターハッシュを取得
     */
    public function getAvatarHash(SocialiteUser $user): ?string
    {
        $avatar = $user->getAvatar();
        return $avatar ? hash('sha256', $avatar) : null;
    }

    /**
     * アバター画像のバイナリデータを取得
     */
    public function getAvatarImageData(SocialiteUser $user): ?string
    {
        $avatarUrl = $user->getAvatar();

        if (!$avatarUrl) {
            return null;
        }

        try {
            // Google画像URLのサイズパラメータを調整（高解像度取得）
            $avatarUrl = str_replace('=s96-c', '=s400-c', $avatarUrl);
            return file_get_contents($avatarUrl);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * プロバイダーのアイコンを取得
     */
    public function getIcon(): ?Htmlable
    {
        return new HtmlString('<svg viewBox="0 0 24 24" width="20" height="20">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
        </svg>');
    }
}

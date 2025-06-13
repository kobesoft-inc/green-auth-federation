<?php

namespace Green\Auth\IdProviders;

use Filament\Actions\Action;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\HtmlString;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Contracts\Provider as SocialiteProvider;
use SocialiteProviders\Azure\Provider as AzureProvider;

/**
 * Microsoft Entra ID 認証プロバイダー
 *
 * テナントIDの指定に対応し、組織固有の認証を提供
 */
class MicrosoftEntraIdProvider extends BaseIdProvider
{
    protected static ?string $driver = 'azure';

    /**
     * プロバイダーの新しいインスタンスを作成
     */
    public static function make(): static
    {
        return new static();
    }

    /**
     * テナントIDを設定
     *
     * @param string $tenantId Microsoft EntraテナントID
     * @return static
     */
    public function tenantId(string $tenantId): static
    {
        $this->config['tenantId'] = $tenantId;
        return $this;
    }

    /**
     * ログインアクションを取得
     */
    public function getLoginAction(): Action
    {
        return Action::make('microsoft_login')
            ->label(__('green-auth-federation::federation.actions.login_with_microsoft'))
            ->icon($this->getIcon())
            ->url($this->getRedirectUrl())
            ->color('gray');
    }

    /**
     * 認証プロバイダーにリダイレクト
     */
    public function redirect(): RedirectResponse
    {
        return $this->getProvider()
            ->with(['prompt' => 'select_account'])
            ->scopes(['openid', 'profile', 'email', 'offline_access', 'User.Read', ...$this->scopes])
            ->stateless()
            ->redirect();
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
            <path fill="#f25022" d="M1 1h10v10H1z"/>
            <path fill="#00a4ef" d="M12 1h10v10H12z"/>
            <path fill="#7fba00" d="M1 12h10v10H1z"/>
            <path fill="#ffb900" d="M12 12h10v10H12z"/>
        </svg>');
    }
}

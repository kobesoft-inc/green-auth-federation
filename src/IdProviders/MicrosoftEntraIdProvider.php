<?php

namespace Green\Auth\IdProviders;

use Filament\Actions\Action;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\HtmlString;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
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
     * @param string $tenant Microsoft EntraテナントID
     * @return static
     */
    public function tenant(string $tenant): static
    {
        $this->config['tenant'] = $tenant;
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
            ->redirect();
    }

    /**
     * Graph APIのクライアントを取得する
     *
     * @param SocialiteUser $user Socialiteユーザー
     * @return PendingRequest HTTPクライアント
     */
    public function getGraphClient(SocialiteUser $user): PendingRequest
    {
        return Http::withHeader('Authorization', 'Bearer ' . $user->token);
    }

    /**
     * ユーザーデータからアバターハッシュを取得
     * Graph APIを使用してプロフィール写真のハッシュ値を取得
     */
    public function getAvatarHash(SocialiteUser $user): ?string
    {
        try {
            $response = $this->getGraphClient($user)
                ->withHeader('content-type', 'application/json')
                ->get('https://graph.microsoft.com/v1.0/me/photo');
                
            Log::info('Microsoft Graph API photo metadata request', [
                'status' => $response->status(),
                'has_token' => !empty($user->token),
            ]);
                
            if ($response->status() === 404) {
                return null;
            }
            
            $photoData = $response->throw()->json();
            $hash = isset($photoData['@odata.mediaEtag']) 
                ? md5($photoData['@odata.mediaEtag']) 
                : null;
                
            Log::info('Microsoft Graph avatar hash generated', [
                'has_hash' => !empty($hash),
                'etag_present' => isset($photoData['@odata.mediaEtag']),
            ]);
            
            return $hash;
        } catch (RequestException $e) {
            Log::warning('Microsoft Graph API photo metadata failed', [
                'error' => $e->getMessage(),
                'status' => $e->getCode(),
            ]);
            return null;
        }
    }

    /**
     * アバター画像のバイナリデータを取得
     * Graph APIを使用してプロフィール写真の実際のデータを取得
     */
    public function getAvatarImageData(SocialiteUser $user): ?string
    {
        try {
            $response = $this->getGraphClient($user)
                ->get('https://graph.microsoft.com/v1.0/me/photo/$value');
                
            Log::info('Microsoft Graph API photo data request', [
                'status' => $response->status(),
                'content_length' => strlen($response->body()),
            ]);
                
            return $response->throw()->body();
        } catch (RequestException $e) {
            Log::warning('Microsoft Graph API photo data failed', [
                'error' => $e->getMessage(),
                'status' => $e->getCode(),
            ]);
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

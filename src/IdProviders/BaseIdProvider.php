<?php

namespace Green\Auth\IdProviders;

use Filament\Actions\Action;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Contracts\Provider as SocialiteProvider;
use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\Manager\Config;

abstract class BaseIdProvider
{
    protected static ?string $driver = null;
    protected ?string $clientId = null;
    protected ?string $clientSecret = null;
    protected array $scopes = [];
    protected bool $autoCreateUser = false;
    protected bool $autoUpdateUser = true;
    protected ?\Closure $userMappingCallback = null;
    protected array $config = [];
    protected ?SocialiteProvider $socialiteProvider = null;

    /**
     * プロバイダーの新しいインスタンスを作成
     */
    abstract public static function make(): static;

    /**
     * ログインアクションを取得
     *
     * @return Action アイコン付きのログインアクション
     */
    abstract public function getLoginAction(): Action;

    /**
     * 認証プロバイダーにリダイレクト
     */
    abstract public function redirect(): RedirectResponse;

    /**
     * ユーザーデータからアバターハッシュを取得
     */
    abstract public function getAvatarHash(SocialiteUser $user): ?string;

    /**
     * アバター画像のバイナリデータを取得
     */
    abstract public function getAvatarImageData(SocialiteUser $user): ?string;

    /**
     * プロバイダーのアイコンを取得
     *
     * @return Htmlable|null アイコンのHtmlableオブジェクトまたはnull
     */
    abstract public function getIcon(): ?Htmlable;

    /**
     * ドライバー名を取得
     */
    public static function getDriver(): string
    {
        if (static::$driver === null) {
            throw new \RuntimeException('Driver name is not configured for ' . static::class);
        }
        return static::$driver;
    }

    /**
     * クライアントIDを設定
     */
    public function clientId(string $clientId): static
    {
        $this->clientId = $clientId;
        return $this;
    }

    /**
     * クライアントシークレットを設定
     */
    public function clientSecret(string $clientSecret): static
    {
        $this->clientSecret = $clientSecret;
        return $this;
    }

    /**
     * スコープを設定
     */
    public function scopes(array $scopes): static
    {
        $this->scopes = $scopes;
        return $this;
    }

    /**
     * Socialite設定を生成
     *
     * プロパティ設定と設定ファイルの両方から設定を取得し、プロパティ設定を優先
     */
    public function getSocialiteConfig(): Config
    {
        // 設定ファイルから基本設定を取得
        $configConfig = $this->getConfigFromFile();

        // clientIdとclientSecretを取得
        $clientId = $this->clientId ?? $configConfig['client_id'] ?? null;
        $clientSecret = $this->clientSecret ?? $configConfig['client_secret'] ?? null;
        if ($clientId === null) {
            throw new \RuntimeException('Client ID is not configured for ' . static::class);
        }
        if ($clientSecret === null) {
            throw new \RuntimeException('Client Secret is not configured for ' . static::class);
        }

        return new Config(
            $clientId,
            $clientSecret,
            $this->getCallbackUrl(),
            $this->config
        );
    }

    /**
     * 設定ファイルからフェデレーション設定を取得
     *
     * config/green-auth.php の guards.ガード名.federations.ドライバー名 から取得
     */
    protected function getConfigFromFile(): array
    {
        $guard = $this->getCurrentGuard();
        $driver = static::getDriver();

        $configPath = "green-auth.guards.{$guard}.federations.{$driver}";

        return config($configPath, []);
    }

    /**
     * 現在のガード名を取得
     *
     * Filamentから現在のガードを取得、フォールバックでwebを使用
     */
    protected function getCurrentGuard(): string
    {
        return filament()->getAuthGuard();
    }

    /**
     * 現在のFilamentパネルIDを取得
     */
    protected function getCurrentPanelId(): string
    {
        return filament()->getCurrentPanel()?->getId();
    }

    /**
     * コールバックURLを生成
     *
     * filament.{panel_id}.auth.federation-callback 形式
     */
    protected function getCallbackUrl(): string
    {
        $panelId = $this->getCurrentPanelId();
        $routeName = "filament.{$panelId}.auth.federation-callback";

        return route($routeName, ['driver' => static::getDriver()]);
    }

    /**
     * リダイレクトURLを生成
     *
     * filament.{panel_id}.auth.federation-redirect 形式
     */
    protected function getRedirectUrl(): string
    {
        $panelId = $this->getCurrentPanelId();
        $routeName = "filament.{$panelId}.auth.federation-redirect";

        return route($routeName, ['driver' => static::getDriver()]);
    }

    /**
     * Socialiteプロバイダーを取得（キャッシュ機能付き）
     */
    public function getProvider(): SocialiteProvider
    {
        if ($this->socialiteProvider === null) {
            $this->socialiteProvider = $this->createSocialiteProvider();
        }
        return $this->socialiteProvider;
    }

    /**
     * Socialiteプロバイダーを作成
     */
    protected function createSocialiteProvider(): SocialiteProvider
    {
        $driver = static::getDriver();
        $config = $this->getSocialiteConfig();

        // サービス設定が存在するかチェック
        $serviceConfigKey = "services.{$driver}";
        if (config($serviceConfigKey, null) === null) {
            config([$serviceConfigKey => $config->get()]);
        }
        return Socialite::driver($driver)
            ->setConfig($config)
            ->stateless();
    }

    /**
     * Socialiteから認証済みユーザーを取得
     */
    public function getAuthenticatedUser(): SocialiteUser
    {
        return $this->getProvider()->user();
    }

    /**
     * ユーザーの自動作成を設定
     */
    public function autoCreateUser(bool $value = true): static
    {
        $this->autoCreateUser = $value;
        return $this;
    }

    /**
     * ユーザーの自動作成が有効かを取得
     */
    public function shouldAutoCreateUser(): bool
    {
        return $this->autoCreateUser;
    }

    /**
     * ユーザーの自動更新を設定
     */
    public function autoUpdateUser(bool $value = true): static
    {
        $this->autoUpdateUser = $value;
        return $this;
    }

    /**
     * ユーザーの自動更新が有効かを取得
     */
    public function shouldAutoUpdateUser(): bool
    {
        return $this->autoUpdateUser;
    }

    /**
     * ユーザー属性マッピングコールバックを定義
     */
    public function mapUserAttributes(\Closure $callback): static
    {
        $this->userMappingCallback = $callback;
        return $this;
    }

    /**
     * Socialiteユーザーをローカルユーザー属性にマッピング
     */
    public function mapUser(SocialiteUser $socialiteUser): array
    {
        $defaultMapping = [
            'name' => $socialiteUser->getName(),
            'email' => $socialiteUser->getEmail(),
        ];

        if ($this->userMappingCallback) {
            $mappedAttributes = ($this->userMappingCallback)($socialiteUser, $defaultMapping);
            return is_array($mappedAttributes) ? $mappedAttributes : $defaultMapping;
        }

        return $defaultMapping;
    }
}

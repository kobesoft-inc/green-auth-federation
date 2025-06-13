# Green Auth Federation

[![Latest Version on Packagist](https://img.shields.io/packagist/v/kobesoft/green-auth-federation.svg?style=flat-square)](https://packagist.org/packages/kobesoft/green-auth-federation)
[![Total Downloads](https://img.shields.io/packagist/dt/kobesoft/green-auth-federation.svg?style=flat-square)](https://packagist.org/packages/kobesoft/green-auth-federation)

Green Auth CoreとFilament管理パネルにフェデレーション認証機能を追加するパッケージです。Microsoft Entra ID（Azure AD）、Google Workspace、その他のOAuth2プロバイダーとのシームレスな統合を提供します。

## 機能

- 🔐 **フェデレーション認証** - 外部IDプロバイダーでのシングルサインオン（SSO）
- 🏢 **Microsoft Entra ID対応** - Azure ADとの深い統合とGraph API活用
- 🔍 **Google Workspace対応** - 組織ドメイン制限とWorkspace統合
- 🎭 **マルチプロバイダー** - 複数のIDプロバイダーを同時利用可能
- 👤 **自動ユーザー作成** - 初回ログイン時の自動アカウント作成
- 🖼️ **アバター同期** - プロバイダーからのプロフィール画像自動同期
- 🔄 **トークン管理** - アクセストークンとリフレッシュトークンの自動管理
- 🌐 **多言語対応** - 英語と日本語の翻訳を内蔵
- ⚡ **Filament統合** - 美しいログインボタンと管理パネル統合

## 対応プロバイダー

### Microsoft Entra ID (Azure AD)
- テナント固有認証
- Microsoft Graph API統合
- プロフィール写真の高品質取得
- 組織アカウント認証

### Google Workspace
- ドメイン制限機能
- Google Cloud Identity統合
- 組織専用認証
- Workspace統合

### その他
- 任意のOAuth2プロバイダーに対応
- カスタムプロバイダーの簡単追加

## インストール

### Composerでのインストール

```bash
composer require kobesoft/green-auth-federation
```

### 前提条件

このパッケージは Green Auth Core を必要とします：

```bash
composer require kobesoft/green-auth-core
php artisan green-auth:install
```

### SocialiteProvidersの設定

必要なSocialiteProvidersをインストール：

```bash
composer require socialiteproviders/microsoft-azure
composer require socialiteproviders/google
```

## セットアップ

### 1. プロバイダーの登録

Filamentパネルプロバイダーでフェデレーションプラグインを登録：

```php
use Green\Auth\GreenAuthFederationPlugin;
use Green\Auth\IdProviders\MicrosoftEntraIdProvider;
use Green\Auth\IdProviders\GoogleIdProvider;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            GreenAuthFederationPlugin::make()
                ->idProvider(
                    MicrosoftEntraIdProvider::make()
                        ->tenant(env('AZURE_TENANT_ID'))
                )
                ->idProvider(
                    GoogleIdProvider::make()
                        ->hostedDomain('example.com') // オプション：ドメイン制限
                ),
        ]);
}
```

### 2. 環境変数の設定

`.env`ファイルに認証情報を追加：

```env
# Microsoft Entra ID / Azure AD
AZURE_CLIENT_ID=your-azure-client-id
AZURE_CLIENT_SECRET=your-azure-client-secret
AZURE_TENANT_ID=your-azure-tenant-id
AZURE_REDIRECT_URI=http://localhost:8000/admin/login/azure/callback

# Google Workspace
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URI=http://localhost:8000/admin/login/google/callback
```

### 3. サービス設定

`config/services.php`にプロバイダー設定を追加：

```php
return [
    // 既存の設定...
    
    'azure' => [
        'client_id' => env('AZURE_CLIENT_ID'),
        'client_secret' => env('AZURE_CLIENT_SECRET'),
        'redirect' => env('AZURE_REDIRECT_URI'),
        'tenant' => env('AZURE_TENANT_ID'),
    ],
    
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],
];
```

### 4. データベースマイグレーション

フェデレーション認証テーブルを作成：

```bash
php artisan migrate
```

## 使用方法

### ログイン画面

設定完了後、Filamentログイン画面に美しいフェデレーション認証ボタンが自動表示されます：

- 「Microsoftでログイン」ボタン
- 「Google Workspaceでログイン」ボタン
- おしゃれな区切り線（「または」ラベル付き）

### プロバイダー固有設定

#### Microsoft Entra ID

```php
MicrosoftEntraIdProvider::make()
    ->tenant('your-tenant-id')  // 必須：テナントID
    ->scopes(['User.Read', 'profile', 'email']) // オプション：追加スコープ
```

**特徴：**
- Microsoft Graph API経由での高品質プロフィール画像取得
- テナント固有認証（マルチテナントアプリでない場合）
- アクセストークンの自動管理

#### Google Workspace

```php
GoogleIdProvider::make()
    ->hostedDomain('example.com')  // オプション：組織ドメイン制限
    ->scopes(['profile', 'email']) // オプション：追加スコープ
```

**特徴：**
- ドメイン制限による組織専用認証
- 高解像度プロフィール画像の取得
- Google Workspace統合

## フェデレーション認証の仕組み

### 認証フロー

1. **ユーザーがプロバイダーボタンをクリック**
   - ユーザーは外部プロバイダーにリダイレクト

2. **外部認証完了**
   - プロバイダーからコールバックURLに戻る

3. **ユーザー情報の取得と処理**
   - プロバイダーからユーザー情報を取得
   - 既存ユーザーの検索またはアカウント自動作成
   - フェデレーション認証情報の保存

4. **トークンとアバターの管理**
   - アクセストークンとリフレッシュトークンの保存
   - プロフィール画像の同期
   - アバター変更の自動検出

5. **Filamentログイン**
   - ユーザーはFilament管理パネルにログイン完了

### データベース構造

`federated_identities`テーブルが認証情報を管理：

```php
Schema::create('federated_identities', function (Blueprint $table) {
    $table->id();
    $table->morphs('authenticatable');           // ユーザーとの関連
    $table->string('driver')->index();           // プロバイダー識別子
    $table->string('provider_user_id')->index(); // プロバイダー側ユーザーID
    $table->text('access_token')->nullable();    // アクセストークン
    $table->timestamp('access_token_expires_at')->nullable(); // トークン有効期限
    $table->text('refresh_token')->nullable();   // リフレッシュトークン
    $table->string('avatar_hash', 64)->nullable(); // アバターハッシュ
    $table->json('provider_data')->nullable();   // プロバイダーデータ
    $table->timestamps();
});
```

## カスタマイゼーション

### カスタムプロバイダーの作成

独自のIDプロバイダーを作成：

```php
use Green\Auth\IdProviders\BaseIdProvider;

class CustomIdProvider extends BaseIdProvider
{
    protected static ?string $driver = 'custom';
    
    public static function make(): static
    {
        return new static();
    }
    
    public function getLoginAction(): Action
    {
        return Action::make('custom_login')
            ->label('Custom Providerでログイン')
            ->icon('heroicon-o-key')
            ->url($this->getRedirectUrl())
            ->color('blue');
    }
    
    public function redirect(): RedirectResponse
    {
        return $this->getProvider()->redirect();
    }
    
    public function getAvatarHash(SocialiteUser $user): ?string
    {
        return $user->getAvatar() ? hash('sha256', $user->getAvatar()) : null;
    }
    
    public function getAvatarImageData(SocialiteUser $user): ?string
    {
        $avatarUrl = $user->getAvatar();
        return $avatarUrl ? file_get_contents($avatarUrl) : null;
    }
    
    public function getIcon(): ?Htmlable
    {
        return new HtmlString('<svg>...</svg>');
    }
}
```

### ユーザー属性のカスタムマッピング

```php
MicrosoftEntraIdProvider::make()
    ->mapUserAttributes(function (SocialiteUser $socialiteUser, array $defaultMapping) {
        return array_merge($defaultMapping, [
            'department' => $socialiteUser->user['department'] ?? null,
            'job_title' => $socialiteUser->user['jobTitle'] ?? null,
        ]);
    })
```

### ログインボタンのカスタマイズ

ビューファイル `resources/views/vendor/green-auth-federation/login-buttons.blade.php` をカスタマイズ：

```blade
@if (!empty($actions))
    {{-- カスタム区切り線 --}}
    <div class="flex items-center my-6">
        <div class="flex-1 border-t border-gray-300"></div>
        <span class="px-3 text-sm text-gray-500">{{ __('or') }}</span>
        <div class="flex-1 border-t border-gray-300"></div>
    </div>

    {{-- カスタムボタンスタイル --}}
    <div class="space-y-3 mb-6">
        @foreach($actions as $action)
            <div class="w-full">
                {{ $action->extraAttributes(['class' => 'w-full justify-center'])->render() }}
            </div>
        @endforeach
    </div>
@endif
```

## トラブルシューティング

### よくある問題

#### 1. "Driver [azure] not supported"

**解決方法：**
- SocialiteProvidersが正しくインストールされているか確認
- ServiceProviderでドライバーが登録されているか確認

#### 2. "AADSTS50194: Application is not configured as a multi-tenant application"

**解決方法：**
- Azure ADアプリケーションでテナント固有のエンドポイントを使用
- `AZURE_TENANT_ID`が正しく設定されているか確認

#### 3. トークンが更新されない

**解決方法：**
- `offline_access`スコープが含まれているか確認
- ログファイルでトークン取得状況をチェック

### デバッグ

ログファイル `storage/logs/laravel.log` でフェデレーション認証の詳細を確認：

```bash
tail -f storage/logs/laravel.log | grep Federation
```

## セキュリティ

### ベストプラクティス

- **環境変数の保護** - `.env`ファイルをバージョン管理に含めない
- **HTTPS必須** - 本番環境では必ずHTTPSを使用
- **トークンの適切な管理** - アクセストークンは暗号化して保存
- **スコープの最小化** - 必要最小限の権限のみを要求

### プライバシー

- プロフィール画像は変更検出により最小限のダウンロードに制限
- ユーザーデータは同意なしに外部と共有されません
- トークンは必要時のみアクセスされます

## サポート

問題や機能リクエストについては、[GitHubイシュートラッカー](https://github.com/kobesoft/green-auth-federation/issues)をご利用ください。

## 貢献

貢献を歓迎します！プルリクエストを送信する前に、すべてのテストが通ることを確認してください。

<?php

namespace Green\Auth\Http\Controllers;

use Exception;
use Green\Auth\GreenAuthFederationPlugin;
use Green\Auth\Models\FederatedIdentity;
use Green\Auth\IdProviders\BaseIdProvider;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Filament\Facades\Filament;
use RuntimeException;

/**
 * フェデレーション認証コントローラー
 *
 * 外部認証プロバイダー（Google、Microsoft等）との連携を処理
 */
class FederationController
{
    /**
     * 認証サービスにリダイレクトする
     *
     * @param string $driver 認証ドライバー名
     * @return RedirectResponse 認証プロバイダーへのリダイレクトレスポンス
     */
    public function redirect(string $driver): RedirectResponse
    {
        $provider = $this->getIdProvider($driver);

        if (!$provider) {
            abort(404, __('green-auth-federation::federation.errors.provider_not_found', ['driver' => $driver]));
        }

        return $provider->redirect();
    }

    /**
     * 認証サービスからのコールバック
     *
     * @param string $driver 認証ドライバー名
     * @return RedirectResponse ログイン後のリダイレクトレスポンス
     */
    public function callback(string $driver): RedirectResponse
    {
        // 認証サービスを取得する
        $provider = $this->getIdProvider($driver);

        if (!$provider) {
            abort(404, __('green-auth-federation::federation.errors.provider_not_found', ['driver' => $driver]));
        }

        // 認証ユーザーを取得する
        $socialiteUser = $provider->getAuthenticatedUser();

        // フェデレーション認証情報を取得または作成
        $federatedIdentity = $this->getFederatedIdentity($provider, $socialiteUser);

        // ローカルユーザーを取得または作成
        $user = $federatedIdentity->authenticatable;
        if (!$user) {
            $user = $this->findOrCreateUser($provider, $socialiteUser);
            if (!$user) {
                abort(403, __('green-auth-federation::federation.errors.login_not_permitted'));
            }

            // フェデレーション認証情報とユーザーを関連付け
            $federatedIdentity->authenticatable()->associate($user);
        }

        // ユーザー情報を更新する
        if ($provider->shouldAutoUpdateUser()) {
            $mappedData = $provider->mapUser($socialiteUser);
            $user->fill($mappedData);
        }

        // アバターの更新チェックと処理
        $this->updateAvatarIfChanged($user, $federatedIdentity, $provider, $socialiteUser);

        // トークン情報を更新
        $federatedIdentity->updateTokens(
            $socialiteUser->token,
            $socialiteUser->refreshToken,
            $socialiteUser->expiresIn ? now()->addSeconds($socialiteUser->expiresIn) : null
        );

        // プロバイダーデータを更新
        $federatedIdentity->provider_data = $socialiteUser->getRaw();

        // 保存
        $user->save();
        $federatedIdentity->save();

        // ログインする
        Filament::auth()->login($user);

        // セッションを再生成する
        session()->regenerate();

        // リダイレクトする
        return redirect()->intended(Filament::getUrl());
    }

    /**
     * フェデレーション認証情報を取得または作成する
     *
     * @param BaseIdProvider $provider 認証プロバイダーインスタンス
     * @param SocialiteUser $socialiteUser Socialiteユーザーインスタンス
     * @return FederatedIdentity フェデレーション認証情報
     */
    private function getFederatedIdentity(BaseIdProvider $provider, SocialiteUser $socialiteUser): FederatedIdentity
    {
        return FederatedIdentity::firstOrNew([
            'driver' => $provider::getDriver(),
            'provider_user_id' => $socialiteUser->getId(),
        ]);
    }

    /**
     * Socialiteの認証情報からユーザーを取得または作成する
     *
     * @param BaseIdProvider $provider 認証プロバイダーインスタンス
     * @param SocialiteUser $socialiteUser Socialiteユーザーインスタンス
     * @return Model|null ユーザーモデルインスタンスまたはnull
     */
    private function findOrCreateUser(BaseIdProvider $provider, SocialiteUser $socialiteUser): ?Model
    {
        $userClass = $this->getAuthProviderModel();
        $user = $userClass::where('email', $socialiteUser->getEmail())->first();

        if ($user) {
            return $user; // 既存のユーザー
        }

        if ($provider->shouldAutoCreateUser()) {
            // 新規ユーザーを作成する
            $mappedData = $provider->mapUser($socialiteUser);
            $user = new $userClass($mappedData);
            $user->save();
            return $user;
        }

        return null; // ログインできない
    }

    /**
     * アバターが変更された場合のみ更新する
     *
     * @param mixed $user ユーザーモデル
     * @param FederatedIdentity $federatedIdentity フェデレーション認証情報
     * @param BaseIdProvider $provider 認証プロバイダー
     * @param SocialiteUser $socialiteUser Socialiteユーザー
     * @return void
     */
    private function updateAvatarIfChanged($user, FederatedIdentity $federatedIdentity, $provider, SocialiteUser $socialiteUser): void
    {
        // HasAvatarトレイトを持っているかチェック
        if (!$this->hasAvatarTrait($user)) {
            return;
        }

        // アバターURLからハッシュを生成
        $avatarUrl = $socialiteUser->getAvatar();
        if (!$avatarUrl) {
            return;
        }

        // ハッシュが変更された場合のみアバターを更新
        if ($federatedIdentity->updateAvatarHash($avatarUrl)) {
            $this->storeAvatarFromProvider($user, $provider, $socialiteUser);
        }
    }

    /**
     * モデルがHasAvatarトレイトを持っているかチェック
     *
     * @param mixed $user ユーザーモデル
     * @return bool
     */
    private function hasAvatarTrait($user): bool
    {
        return method_exists($user, 'getAvatarUrl') &&
            method_exists($user, 'storeAvatar');
    }

    /**
     * プロバイダーからアバターを取得してstoreAvatarを使用して保存
     *
     * @param mixed $user HasAvatarトレイトを持つユーザーモデル
     * @param BaseIdProvider $provider 認証プロバイダー
     * @param SocialiteUser $socialiteUser Socialiteユーザー
     * @return void
     */
    private function storeAvatarFromProvider($user, $provider, SocialiteUser $socialiteUser): void
    {
        // アバター画像データを取得
        $avatarData = $provider->getAvatarImageData($socialiteUser);

        if (!$avatarData) {
            return;
        }

        // アバターファイルの拡張子を取得
        $extension = $this->getAvatarExtension($avatarData);
        if (!$extension) {
            return;
        }

        // 一時ファイルを作成
        $tempPath = tempnam(sys_get_temp_dir(), 'avatar_') . '.' . $extension;
        file_put_contents($tempPath, $avatarData);

        try {
            // UploadedFileオブジェクトを作成
            $uploadedFile = new UploadedFile(
                $tempPath,
                'avatar.' . $extension,
                $this->getMimeTypeFromExtension($extension),
                null,
                true // test mode (allows temporary files)
            );

            // HasAvatarトレイトのstoreAvatarメソッドを使用
            $user->storeAvatar($uploadedFile);

        } finally {
            // 一時ファイルを削除
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    }

    /**
     * 拡張子からMIMEタイプを取得
     *
     * @param string $extension ファイル拡張子
     * @return string MIMEタイプ
     */
    private function getMimeTypeFromExtension(string $extension): string
    {
        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
    }

    /**
     * アバターの拡張子を取得する
     *
     * @param string $contents アバターのデータ
     * @return string|null 拡張子
     */
    private function getAvatarExtension(string $contents): ?string
    {
        if (!extension_loaded('fileinfo')) {
            return null;
        }

        $finfo = finfo_open();
        $mimeType = finfo_buffer($finfo, $contents, FILEINFO_MIME_TYPE);
        finfo_close($finfo);

        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => null,
        };
    }

    /**
     * 現在のGuardのユーザーモデルのクラス名を取得する
     *
     * @return string ユーザーモデルのクラス名
     */
    protected function getAuthProviderModel(): string
    {
        $guard = Auth::guard(Filament::getAuthGuard());
        $provider = $guard->getProvider();

        if (!$provider instanceof EloquentUserProvider) {
            throw new RuntimeException('The current provider is not an EloquentUserProvider.');
        }

        return $provider->getModel();
    }

    /**
     * 現在のパネルのプラグインからIDプロバイダーを取得
     *
     * @param string $driver ドライバー名
     * @return BaseIdProvider|null IDプロバイダーインスタンスまたはnull
     * @throws Exception
     */
    protected function getIdProvider(string $driver): ?BaseIdProvider
    {
        $currentPanel = filament()->getCurrentPanel();

        if (!$currentPanel || !$currentPanel->hasPlugin('green-auth-idp')) {
            return null;
        }

        /** @var GreenAuthFederationPlugin $plugin */
        $plugin = $currentPanel->getPlugin('green-auth-idp');

        return $plugin->getIdProvider($driver);
    }
}

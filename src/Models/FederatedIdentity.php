<?php

namespace Green\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * フェデレーション認証情報モデル
 * 
 * ユーザーテーブルと関連付けられるフェデレーション認証の情報を管理
 * 
 * @property int $id
 * @property string $authenticatable_type ユーザーモデルのクラス名（users, admin_users等）
 * @property int $authenticatable_id ユーザーモデルのID
 * @property string $driver 認証プロバイダー識別子（google, microsoft等）
 * @property string $provider_user_id 認証プロバイダー側のユーザーID
 * @property string|null $access_token アクセストークン
 * @property \Carbon\Carbon|null $access_token_expires_at アクセストークンの有効期限
 * @property string|null $refresh_token リフレッシュトークン
 * @property string|null $avatar_hash アバター画像のSHA256ハッシュ値
 * @property array|null $provider_data プロバイダーから提供されたユーザーデータ（JSON）
 * @property \Carbon\Carbon $created_at 作成日時
 * @property \Carbon\Carbon $updated_at 更新日時
 */
class FederatedIdentity extends Model
{
    /**
     * 一括代入可能な属性
     */
    protected $fillable = [
        'driver',                    // 認証プロバイダー識別子
        'provider_user_id',          // プロバイダー側ユーザーID
        'access_token',              // アクセストークン
        'access_token_expires_at',   // トークン有効期限
        'refresh_token',             // リフレッシュトークン
        'avatar_hash',               // アバターハッシュ値
        'provider_data',             // プロバイダーデータ
    ];

    /**
     * 属性のキャスト設定
     */
    protected $casts = [
        'access_token_expires_at' => 'datetime',  // 日時型にキャスト
        'provider_data' => 'array',               // 配列型にキャスト
    ];

    /**
     * JSONシリアライゼーション時に隠す属性
     */
    protected $hidden = [
        'access_token',     // セキュリティのため隠す
        'refresh_token',    // セキュリティのため隠す
    ];

    /**
     * 認証可能なモデルとのポリモーフィックリレーション
     * 
     * users、admin_users等、様々なユーザーテーブルと関連付け可能
     * 
     * @return MorphTo
     */
    public function authenticatable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * アクセストークンが期限切れかどうかを判定
     * 
     * @return bool トークンが期限切れの場合true
     */
    public function isTokenExpired(): bool
    {
        return $this->access_token_expires_at && $this->access_token_expires_at->isPast();
    }

    /**
     * トークン情報を更新
     * 
     * 認証プロバイダーから新しいトークンを取得した際に使用
     * 
     * @param string $accessToken アクセストークン
     * @param string|null $refreshToken リフレッシュトークン（nullの場合は既存値を保持）
     * @param \DateTimeInterface|null $expiresAt トークンの有効期限
     * @return void
     */
    public function updateTokens(string $accessToken, ?string $refreshToken = null, ?\DateTimeInterface $expiresAt = null): void
    {
        $this->update([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken ?: $this->refresh_token,
            'access_token_expires_at' => $expiresAt,
        ]);
    }

    /**
     * アバターハッシュを更新（変更があった場合のみ）
     * 
     * プロバイダー側でアバターが更新されたことを検出するために使用
     * 
     * @param string $avatarUrl アバター画像のURL
     * @return bool ハッシュが更新された場合true
     */
    public function updateAvatarHash(string $avatarUrl): bool
    {
        $newHash = hash('sha256', $avatarUrl);

        if ($this->avatar_hash !== $newHash) {
            $this->update(['avatar_hash' => $newHash]);
            return true; // ハッシュが変更された
        }

        return false; // ハッシュに変更なし
    }

    /**
     * 指定されたドライバーでフィルタリングするクエリスコープ
     * 
     * 使用例: FederatedIdentity::byDriver('google')->get()
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $driver プロバイダー識別子
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByDriver($query, string $driver)
    {
        return $query->where('driver', $driver);
    }

    /**
     * 指定されたプロバイダーユーザーIDでフィルタリングするクエリスコープ
     * 
     * 使用例: FederatedIdentity::byProviderUserId('12345')->first()
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $providerUserId プロバイダー側のユーザーID
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByProviderUserId($query, string $providerUserId)
    {
        return $query->where('provider_user_id', $providerUserId);
    }
}

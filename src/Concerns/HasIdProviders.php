<?php

namespace Green\Auth\Concerns;

use Green\Auth\IdProviders\BaseIdProvider;
use InvalidArgumentException;

/**
 * IDプロバイダー管理機能を提供するトレイト
 *
 * このトレイトを使用することで、クラスにIDプロバイダーの登録・取得機能を追加できます。
 * Filamentプラグインなどで、フェデレーション認証のプロバイダー管理を簡単に実装できます。
 */
trait HasIdProviders
{
    /**
     * @var array<string, BaseIdProvider> IDプロバイダー配列
     */
    protected array $idProviders = [];


    /**
     * 単一のIDプロバイダーを登録
     *
     * パネルに対して単一のIDプロバイダーを登録します。
     *
     * @param BaseIdProvider $provider IDプロバイダーインスタンス
     * @return static メソッドチェーンのための自分自身
     */
    public function idProvider(BaseIdProvider $provider): static
    {
        $this->idProviders[$provider::class::getDriver()] = $provider;
        return $this;
    }

    /**
     * 複数のIDプロバイダーを一括登録
     *
     * パネルに対して複数のIDプロバイダーを一括登録します。
     *
     * @param array<string, BaseIdProvider>|array<BaseIdProvider> $providers プロバイダー配列
     * @return static メソッドチェーンのための自分自身
     */
    public function idProviders(array $providers): static
    {
        foreach ($providers as $name => $provider) {
            if (!$provider instanceof BaseIdProvider) {
                throw new InvalidArgumentException(
                    sprintf('Provider must be an instance of %s, %s given', BaseIdProvider::class, get_class($provider))
                );
            }
            $this->idProvider($provider);
        }
        return $this;
    }

    /**
     * 登録された全IDプロバイダーを取得
     *
     * パネルに登録された全IDプロバイダーを取得します。
     *
     * @return array<string, BaseIdProvider> IDプロバイダー配列
     */
    public function getIdProviders(): array
    {
        return $this->idProviders;
    }

    /**
     * 指定されたドライバー名でIDプロバイダーを取得
     *
     * @param string $driver ドライバー名
     * @return BaseIdProvider|null IDプロバイダーインスタンスまたはnull
     */
    public function getIdProvider(string $driver): ?BaseIdProvider
    {
        return $this->idProviders[$driver] ?? null;
    }

    /**
     * 指定されたドライバーが存在するかチェック
     *
     * @param string $driver ドライバー名
     * @return bool 存在する場合true
     */
    public function hasIdProvider(string $driver): bool
    {
        return isset($this->idProviders[$driver]);
    }
}

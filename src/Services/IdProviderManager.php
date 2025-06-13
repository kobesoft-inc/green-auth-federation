<?php

namespace Green\Auth\Services;

use Green\Auth\IdProviders\BaseIdProvider;
use InvalidArgumentException;

class IdProviderManager
{
    /**
     * @var array<string, array<string, BaseIdProvider>>
     */
    protected array $providers = [];

    /**
     * ガード用のIDプロバイダーを登録
     *
     * @param string $guard ガード名
     * @param array<string, BaseIdProvider>|BaseIdProvider $providers プロバイダー配列またはプロバイダーインスタンス
     * @return void
     */
    public function register(string $guard, array|BaseIdProvider $providers): void
    {
        if ($providers instanceof BaseIdProvider) {
            $className = get_class($providers);
            $providerName = $this->getProviderName($className);
            $this->providers[$guard][$providerName] = $providers;
        } else {
            foreach ($providers as $name => $provider) {
                if (!$provider instanceof BaseIdProvider) {
                    throw new InvalidArgumentException(
                        sprintf('Provider must be an instance of %s, %s given', BaseIdProvider::class, get_class($provider))
                    );
                }

                $providerName = is_string($name) ? $name : $this->getProviderName(get_class($provider));
                $this->providers[$guard][$providerName] = $provider;
            }
        }
    }

    /**
     * 指定されたガードとプロバイダー名でIDプロバイダーを取得
     *
     * @param string $guard ガード名
     * @param string $provider プロバイダー名
     * @return BaseIdProvider|null プロバイダーインスタンスまたはnull
     */
    public function get(string $guard, string $provider): ?BaseIdProvider
    {
        return $this->providers[$guard][$provider] ?? null;
    }

    /**
     * 指定されたガードの全IDプロバイダーを取得
     *
     * @param string $guard ガード名
     * @return array<string, BaseIdProvider> プロバイダー配列
     */
    public function getProviders(string $guard): array
    {
        return $this->providers[$guard] ?? [];
    }

    /**
     * 指定されたガードの全IDプロバイダーを取得
     *
     * @param string $guard ガード名
     * @return array<string, BaseIdProvider> プロバイダー配列
     */
    public function all(string $guard): array
    {
        return $this->getProviders($guard);
    }

    /**
     * 指定されたガードとプロバイダー名でIDプロバイダーが存在するかチェック
     *
     * @param string $guard ガード名
     * @param string $provider プロバイダー名
     * @return bool 存在する場合true
     */
    public function has(string $guard, string $provider): bool
    {
        return isset($this->providers[$guard][$provider]);
    }

    /**
     * 登録済みの全ガード名を取得
     *
     * @return array<string> ガード名の配列
     */
    public function getGuards(): array
    {
        return array_keys($this->providers);
    }

    /**
     * クラス名からプロバイダー名を抽出
     *
     * @param string $className クラス名
     * @return string プロバイダー名
     */
    protected function getProviderName(string $className): string
    {
        $parts = explode('\\', $className);
        $name = end($parts);

        // 一般的なサフィックスを削除
        $name = preg_replace('/(IdProvider|Provider)$/i', '', $name);

        // snake_caseに変換
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
    }
}

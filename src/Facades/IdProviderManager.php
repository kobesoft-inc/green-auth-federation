<?php

namespace Green\Auth\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * IDプロバイダーマネージャーのファサード
 * 
 * @method static void register(string $guard, array|\Green\Auth\IdProviders\BaseIdProvider $providers) ガード用のIDプロバイダーを登録する
 * @method static \Green\Auth\IdProviders\BaseIdProvider|null get(string $guard, string $provider) 指定されたガードとプロバイダー名でIDプロバイダーを取得する
 * @method static array getProviders(string $guard) 指定されたガードの全IDプロバイダーを取得する
 * @method static array all(string $guard) 指定されたガードの全IDプロバイダーを取得する
 * @method static bool has(string $guard, string $provider) 指定されたガードとプロバイダー名でIDプロバイダーが存在するかチェックする
 * @method static array getGuards() 登録済みの全ガード名を取得する
 *
 * @see \Green\Auth\Services\IdProviderManager
 */
class IdProviderManager extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'green-auth.federation.id-provider-manager';
    }
}

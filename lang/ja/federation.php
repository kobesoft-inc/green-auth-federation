<?php

return [
    /*
    |--------------------------------------------------------------------------
    | フェデレーション認証言語ファイル
    |--------------------------------------------------------------------------
    |
    | 以下の言語行はフェデレーション認証機能で使用される様々なメッセージです。
    |
    */

    'errors' => [
        'provider_not_found' => "プロバイダー ':driver' が見つかりません",
        'login_not_permitted' => 'ログインが許可されていません',
        'client_id_not_configured' => 'Client ID is not configured for :class',
        'client_secret_not_configured' => 'Client Secret is not configured for :class',
        'driver_not_configured' => 'Driver name is not configured for :class',
        'unknown_driver' => 'Unknown driver: :driver',
    ],

    'actions' => [
        'login_with_microsoft' => 'Microsoftでログイン',
        'login_with_google' => 'Googleでログイン',
    ],

    'or' => 'または',
];

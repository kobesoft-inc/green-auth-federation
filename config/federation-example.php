<?php

/**
 * green-auth.php 設定ファイルに追加するフェデレーション認証設定の例
 * 
 * 以下の設定を green-auth.guards.ガード名 の中に追加してください
 */

return [
    'guards' => [
        'web' => [
            // ... 既存の設定 ...

            /**
             * フェデレーション認証設定
             */
            'federations' => [
                /**
                 * Google Cloud Identity認証設定
                 */
                'google' => [
                    'client_id' => env('GOOGLE_CLIENT_ID'),
                    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
                    // 組織のドメインに制限（Google Workspace/Cloud Identity）
                    'hosted_domain' => env('GOOGLE_HOSTED_DOMAIN'), // 例：example.com
                    // 追加のスコープ
                    'scopes' => ['openid', 'profile', 'email'],
                ],

                /**
                 * Microsoft Entra ID認証設定
                 */
                'microsoft-azure' => [
                    'client_id' => env('MICROSOFT_CLIENT_ID'),
                    'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
                    'tenant_id' => env('MICROSOFT_TENANT_ID', 'common'), // 'common', 'organizations', または特定のテナントID
                ],

                /**
                 * GitHub認証設定（例）
                 */
                'github' => [
                    'client_id' => env('GITHUB_CLIENT_ID'),
                    'client_secret' => env('GITHUB_CLIENT_SECRET'),
                ],
            ],
        ],

        'admin' => [
            // ... 管理者ガードの設定 ...

            /**
             * 管理者専用フェデレーション認証設定
             */
            'federations' => [
                'microsoft-azure' => [
                    'client_id' => env('ADMIN_MICROSOFT_CLIENT_ID'),
                    'client_secret' => env('ADMIN_MICROSOFT_CLIENT_SECRET'),
                    'tenant_id' => env('ADMIN_MICROSOFT_TENANT_ID'), // 管理者専用テナント
                ],
            ],
        ],
    ],
];
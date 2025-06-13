<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Federation Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used by the federation authentication
    | functionality.
    |
    */

    'errors' => [
        'provider_not_found' => "Provider ':driver' not found",
        'login_not_permitted' => 'Login not permitted',
        'client_id_not_configured' => 'Client ID is not configured for :class',
        'client_secret_not_configured' => 'Client Secret is not configured for :class',
        'driver_not_configured' => 'Driver name is not configured for :class',
        'unknown_driver' => 'Unknown driver: :driver',
    ],

    'actions' => [
        'login_with_microsoft' => 'Sign in with Microsoft',
        'login_with_google' => 'Sign in with Google',
    ],

    'or' => 'or',
];

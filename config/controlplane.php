<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ControlDesk base URL
    |--------------------------------------------------------------------------
    |
    | Enrollment and heartbeat calls go to {url}/api/v1/.... When empty,
    | redemption is unavailable and the setup wizard offers the offline and
    | file-import exits (never a dead end).
    |
    */
    'url' => env('CONTROL_PLANE_URL'),

    /*
    |--------------------------------------------------------------------------
    | HTTP timeout (seconds) for ControlDesk calls
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('CONTROL_PLANE_TIMEOUT', 8),

    /*
    |--------------------------------------------------------------------------
    | Licence-file public key (base64-encoded 32-byte ed25519 key)
    |--------------------------------------------------------------------------
    |
    | Verifies imported licence blobs. Set CONTROL_PLANE_PUBLIC_KEY from ops;
    | when empty, file import reports that no key is configured.
    |
    */
    'public_key' => env('CONTROL_PLANE_PUBLIC_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Application version reported at enrollment
    |--------------------------------------------------------------------------
    */
    'app_version' => env('APP_VERSION', '1.0.0'),

];

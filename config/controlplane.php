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
    | ControlDesk public key (the single ed25519 key)
    |--------------------------------------------------------------------------
    |
    | Verifies imported licence blobs AND signed demo-key documents (one
    | keypair signs both — see ControlPlaneKeys). Set
    | CONTROL_PLANE_PUBLIC_KEY from ops. Hex (as printed by ControlDesk)
    | or base64. When empty, file import and demo-key entry both report
    | that no key is configured.
    |
    */
    'public_key' => env('CONTROL_PLANE_PUBLIC_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Application version reported at enrollment
    |--------------------------------------------------------------------------
    */
    'app_version' => env('APP_VERSION', '1.0.0'),

    /*
    |--------------------------------------------------------------------------
    | This deployment's identity (written by enrollment, read via config)
    |--------------------------------------------------------------------------
    |
    | Always read through config (never env() at runtime): config survives
    | config:cache and is trivially overridable in tests, while putenv()
    | cannot shadow a real $_SERVER entry.
    |
    */
    'deployment_uuid' => env('DEPLOYMENT_UUID'),

    'token' => env('CONTROL_PLANE_TOKEN'),

];

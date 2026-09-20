<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cloudflare Turnstile (landing quote form spam armor)
    |--------------------------------------------------------------------------
    |
    | When both keys are set, the landing quote form renders the Turnstile
    | widget and submissions without a valid token are rejected with 422.
    | When unset (local/dev/tests), verification is skipped entirely.
    |
    */
    'turnstile_site_key' => env('TURNSTILE_SITE_KEY'),

    'turnstile_secret_key' => env('TURNSTILE_SECRET_KEY'),

];

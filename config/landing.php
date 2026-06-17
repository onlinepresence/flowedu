<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Landing Page Enabled
    |--------------------------------------------------------------------------
    |
    | When true, the root route '/' will serve the marketing landing page.
    | When false, the root route '/' will redirect straight to '/login'.
    |
    */
    'enabled' => filter_var(env('LANDING_PAGE_ENABLED', false), FILTER_VALIDATE_BOOL),
];

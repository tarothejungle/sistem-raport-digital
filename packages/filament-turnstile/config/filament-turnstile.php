<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cloudflare Turnstile keys
    |--------------------------------------------------------------------------
    |
    | Get these from the Cloudflare dashboard → Turnstile.
    | For local / CI, use Cloudflare's always-pass test keys:
    | site:   1x00000000000000000000AA
    | secret: 1x0000000000000000000000000000000AA
    |
    */

    'site_key' => env('TURNSTILE_SITE_KEY'),

    'secret_key' => env('TURNSTILE_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Siteverify endpoint
    |--------------------------------------------------------------------------
    */

    'verify_url' => env(
        'TURNSTILE_VERIFY_URL',
        'https://challenges.cloudflare.com/turnstile/v0/siteverify',
    ),

    'connect_timeout' => (int) env('TURNSTILE_CONNECT_TIMEOUT', 5),

    'timeout' => (int) env('TURNSTILE_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Widget defaults
    |--------------------------------------------------------------------------
    |
    | theme: auto | light | dark
    | size:  normal | flexible | compact
    |
    */

    'theme' => env('TURNSTILE_THEME', 'auto'),

    'size' => env('TURNSTILE_SIZE', 'flexible'),

    'language' => env('TURNSTILE_LANGUAGE'),

    /*
    |--------------------------------------------------------------------------
    | Livewire / browser event used to refresh the widget after failures
    |--------------------------------------------------------------------------
    */

    'reset_event' => env('TURNSTILE_RESET_EVENT', 'turnstile-reset'),

];

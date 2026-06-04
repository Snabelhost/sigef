<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'telcosms' => [
        'api_key' => env('TELCOSMS_API_KEY', 'prd09933ffaa3022ca9d71dc39719'),
        'api_url' => env('TELCOSMS_API_URL', 'https://telcosms.co.ao/send_message'),
    ],

    'piips' => [
        'base_url' => env('PIIPS_API_URL', 'http://localhost:3333/api/v1/integration'),
        'api_key' => env('PIIPS_API_KEY', ''),
        'storage_url' => env('PIIPS_STORAGE_URL', 'http://localhost:3333'),
        'cache_ttl' => env('PIIPS_CACHE_TTL', 3600),
    ],

];

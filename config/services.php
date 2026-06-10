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
        'api_key' => env('TELCOSMS_API_KEY', ''),
        'api_url' => env('TELCOSMS_API_URL', 'https://telcosms.co.ao/send_message'),
        'verify_ssl' => env('TELCOSMS_VERIFY_SSL', true),
    ],

    'piips' => [
        'base_url' => env('PIIPS_API_URL', 'http://localhost:3333/api/v1/integration'),
        'api_key' => env('PIIPS_API_KEY', ''),
        'storage_url' => env('PIIPS_STORAGE_URL', 'http://localhost:3333'),
        'cache_ttl' => env('PIIPS_CACHE_TTL', 3600),
    ],

    'identity_card_lookup' => [
        'url' => env('IDENTITY_CARD_LOOKUP_URL', 'https://consulta.edgarsingui.ao/consultar'),
    ],

    'recruitment_portal' => [
        'candidates_url' => env('RECRUITMENT_PORTAL_CANDIDATES_URL', 'http://10.110.2.18/api/candidates'),
        'timeout' => env('RECRUITMENT_PORTAL_TIMEOUT', 25),
    ],

    'siga' => [
        'base_url' => env('SIGA_API_BASE_URL'),
        'api_key' => env('SIGA_API_KEY'),
        'token' => env('SIGA_API_TOKEN'),
        'verify_ssl' => env('SIGA_API_VERIFY_SSL', true),
        'timeout' => env('SIGA_API_TIMEOUT', 20),
        'connect_timeout' => env('SIGA_API_CONNECT_TIMEOUT', 5),
        'retries' => env('SIGA_API_RETRIES', 2),
        'cache_ttl' => env('SIGA_API_CACHE_TTL', 900),
        'stale_cache_ttl' => env('SIGA_API_STALE_CACHE_TTL', 86400),
        'failure_backoff' => env('SIGA_API_FAILURE_BACKOFF', 60),
        'per_page' => env('SIGA_API_PER_PAGE', 1000),
        'max_pages' => env('SIGA_API_MAX_PAGES', 10),
        'endpoints' => [
            'institution_students' => env('SIGA_API_INSTITUTION_STUDENTS_ENDPOINT'),
            'students_by_course' => env('SIGA_API_STUDENTS_BY_COURSE_ENDPOINT'),
            'students' => env('SIGA_API_STUDENTS_ENDPOINT', '/api/v1/students'),
            'programs' => env('SIGA_API_PROGRAMS_ENDPOINT', '/api/v1/catalog/programs'),
        ],
    ],

];

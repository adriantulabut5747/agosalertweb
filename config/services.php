<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    // Supabase Auth + Storage (same project as the Flutter app). The
    // publishable key is public by design; never put the secret key here.
    'supabase' => [
        'url' => env('SUPABASE_URL'),
        'key' => env('SUPABASE_KEY'),
    ],

    // The Android app the landing page's store badges offer while the app
    // isn't on Google Play / the App Store: the newest GitHub release,
    // built by the Flutter repo's .github/workflows/build-apk.yml.
    // Change it here (or APK_URL in .env) when the app moves.
    'apk_url' => env('APK_URL', 'https://github.com/adriantulabut5747/agosalertapp/releases/latest/download/AgosAlert.apk'),

];

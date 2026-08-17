<?php

return [

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'google' => [
        'client_id'    => env('GOOGLE_CLIENT_ID'),
        'client_secret'=> env('GOOGLE_CLIENT_SECRET'),
        'redirect'     => env('GOOGLE_REDIRECT_URI', 'http://localhost:8000/api/gmail/callback'),
    ],

    'ai' => [
        'provider'    => env('AI_PROVIDER', 'openai'),
        'api_key'     => env('AI_API_KEY'),
        'model'       => env('AI_MODEL', 'gpt-4o-mini'),
        'temperature' => (float) env('AI_TEMPERATURE', 0.7),
        'max_tokens'  => (int)   env('AI_MAX_TOKENS', 1000),
    ],

    // Adzuna — free tier, register at https://developer.adzuna.com
    // Supports region (gb, us, au, ca, ng, za, de, fr, in, nl, nz, sg, za)
    'adzuna' => [
        'app_id'  => env('ADZUNA_APP_ID'),
        'app_key' => env('ADZUNA_APP_KEY'),
        'country' => env('ADZUNA_COUNTRY', 'gb'),
    ],

    // The Muse — works without key (rate-limited) or with free key
    // https://www.themuse.com/developers/api/v2
    'the_muse' => [
        'api_key' => env('THE_MUSE_API_KEY'),
    ],

];

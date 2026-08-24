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

    // Google OAuth redirect URI — needed at the server level for the callback URL.
    // client_id and client_secret are stored per-user in AutomationSettings (encrypted).
    'google' => [
        'redirect' => env('GOOGLE_REDIRECT_URI', 'http://localhost:8000/api/gmail/callback'),
    ],

    // AI defaults — provider/model/temperature are overridden per-user in AutomationSettings.
    // No api_key here: keys are stored encrypted in AutomationSettings.ai_api_key_encrypted.
    'ai' => [
        'provider'    => 'openai',
        'model'       => 'gpt-4o-mini',
        'temperature' => 0.7,
        'max_tokens'  => 1000,
    ],

    // Adzuna — server-wide job board key (free tier).
    // Register at https://developer.adzuna.com
    'adzuna' => [
        'app_id'  => env('ADZUNA_APP_ID'),
        'app_key' => env('ADZUNA_APP_KEY'),
        'country' => env('ADZUNA_COUNTRY', 'gb'),
    ],

    // The Muse — works without a key (rate-limited) or with a free key.
    // https://www.themuse.com/developers/api/v2
    'the_muse' => [
        'api_key' => env('THE_MUSE_API_KEY'),
    ],

];

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

    'groq' => [
        'key' => env('GROQ_API_KEY'),
        'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
        'model' => env('GROQ_MODEL', 'openai/gpt-oss-20b'),
    ],

    'google_search_console' => [
        'client_id' => env('GOOGLE_SEARCH_CONSOLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_SEARCH_CONSOLE_CLIENT_SECRET'),
        'refresh_token' => env('GOOGLE_SEARCH_CONSOLE_REFRESH_TOKEN'),
        'token_url' => env('GOOGLE_SEARCH_CONSOLE_TOKEN_URL', 'https://oauth2.googleapis.com/token'),
        'base_url' => env('GOOGLE_SEARCH_CONSOLE_BASE_URL', 'https://www.googleapis.com/webmasters/v3'),
    ],

    'google_trends_bigquery' => [
        'project_id' => env('GOOGLE_TRENDS_BIGQUERY_PROJECT_ID'),
        'client_email' => env('GOOGLE_TRENDS_BIGQUERY_CLIENT_EMAIL'),
        'private_key' => env('GOOGLE_TRENDS_BIGQUERY_PRIVATE_KEY'),
        'token_url' => env('GOOGLE_TRENDS_BIGQUERY_TOKEN_URL', 'https://oauth2.googleapis.com/token'),
        'base_url' => env('GOOGLE_TRENDS_BIGQUERY_BASE_URL', 'https://bigquery.googleapis.com/bigquery/v2'),
        'dataset' => env('GOOGLE_TRENDS_BIGQUERY_DATASET', 'bigquery-public-data.google_trends'),
        'location' => env('GOOGLE_TRENDS_BIGQUERY_LOCATION', 'US'),
        'top_terms_table' => env('GOOGLE_TRENDS_BIGQUERY_TOP_TERMS_TABLE', 'international_top_terms'),
        'rising_terms_table' => env('GOOGLE_TRENDS_BIGQUERY_RISING_TERMS_TABLE', 'international_top_rising_terms'),
    ],

];

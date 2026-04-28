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
        'token' => env('POSTMARK_TOKEN'),
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

    'aws' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'textract' => [
        'region' => env('AWS_TEXTRACT_REGION', env('AWS_DEFAULT_REGION', 'us-east-1')),
        'version' => 'latest',
        'access_key' => env('AWS_TEXTRACT_ACCESS_KEY_ID', env('AWS_ACCESS_KEY_ID')),
        'secret_key' => env('AWS_TEXTRACT_SECRET_ACCESS_KEY', env('AWS_SECRET_ACCESS_KEY')),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Business API
    |--------------------------------------------------------------------------
    |
    | Credentials for the Meta WhatsApp Business API (Cloud API).
    | Receipts sent via WhatsApp are downloaded, stored in S3, then
    | processed through Textract + Bedrock the same as web uploads.
    |
    */

    'whatsapp' => [
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Paddle Price IDs
    |--------------------------------------------------------------------------
    |
    | Map each plan to its Paddle price identifier. These are used by the
    | PlanSeeder and the billing UI to initiate Paddle checkouts.
    | Set these in your .env (e.g. PADDLE_PRICE_STARTER=pri_xxx).
    |
    */

    'paddle' => [
        'prices' => [
            'starter' => env('PADDLE_PRICE_STARTER'),
            'professional' => env('PADDLE_PRICE_PROFESSIONAL'),
            'business' => env('PADDLE_PRICE_BUSINESS'),
            'enterprise' => env('PADDLE_PRICE_ENTERPRISE'),
        ],
    ],

];

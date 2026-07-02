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
        'access_token'      => env('WHATSAPP_ACCESS_TOKEN'),
        'verify_token'      => env('WHATSAPP_VERIFY_TOKEN'),
        'app_secret'        => env('WHATSAPP_APP_SECRET'),
        'phone_number_id'   => env('WHATSAPP_PHONE_NUMBER_ID'),
        'api_version'       => env('WHATSAPP_API_VERSION', 'v21.0'),
        'typing_indicator'  => env('WHATSAPP_TYPING_INDICATOR_ENABLED', true),
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

    /*
    |--------------------------------------------------------------------------
    | Agent Tools
    |--------------------------------------------------------------------------
    |
    | Shared secret for the /api/agent/tools/dispatch endpoint. The external
    | AI agent must supply this as a Bearer token. Generate with:
    |   php artisan key:generate --show | head -c 64
    | or any cryptographically random string of 32+ chars.
    |
    */

    'agent_tools' => [
        'secret'       => env('AGENT_TOOLS_SECRET'),
        'hourly_limit' => (int) env('INVOKE_HOURLY_LIMIT', 50),
    ],

    'lookup' => [
        'monthly_cap' => (int) env('LOOKUP_MONTHLY_CAP', 50),
        // Max per-retailer Serper queries per price check. Each query counts
        // against the per-user monthly cap, so fan-out trades depth per check
        // for number of checks per month (3 → ~16 checks/user/month at cap 50).
        'retailer_fanout' => (int) env('LOOKUP_RETAILER_FANOUT', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Retention
    |--------------------------------------------------------------------------
    |
    | How long (days) raw source artifacts are kept before the
    | finvixy:prune-receipts command may remove them: raw receipt images in S3
    | and stored WhatsApp webhook payloads. Derived expense records are NOT
    | pruned — only the raw source artifacts. Owner-configurable.
    |
    */

    'retention' => [
        'days' => (int) env('RETENTION_DAYS', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | AgentCore Runtime
    |--------------------------------------------------------------------------
    |
    | HTTP endpoint for the AgentCore runtime (POST /invocations).
    | Dev:  http://localhost:8083   (agentcore dev port)
    | Prod: https://<deployed-endpoint>
    |
    */

    'agentcore' => [
        'mode'             => env('AGENTCORE_MODE', 'local'),
        'endpoint'         => env('AGENTCORE_ENDPOINT', 'http://127.0.0.1:8081'),
        'runtime_arn'      => env('AGENTCORE_RUNTIME_ARN'),
        'region'           => env('AGENTCORE_REGION', 'eu-central-1'),
        'history_turns'    => (int) env('AGENT_HISTORY_TURNS', 8),
        'idle_reset_hours' => (int) env('AGENT_SESSION_IDLE_RESET', 2),

        // Model name used for usage logging + cost estimation on the Laravel
        // side. Must track the BEDROCK_MODEL_ID set on the agent runtime
        // (e.g. claude-haiku-4-5 ↔ ...claude-haiku-4-5...; claude-sonnet-4-6 ↔
        // eu.anthropic.claude-sonnet-4-6).
        'model_name' => env('AGENT_MODEL_NAME', 'claude-haiku-4-5'),

        // Pre-emptive per-user cap on agent invocations per calendar month,
        // checked BEFORE the Bedrock call. Only real runs are counted.
        'monthly_invocation_cap' => (int) env('MONTHLY_INVOCATION_CAP', 150),

        // Inactivity sweeper: minutes of silence before a session is closed
        // and its conversation context cleared. Sweeper runs every 5 minutes
        // but stays inert until SESSION_SWEEPER_ENABLED=true.
        'inactivity_minutes' => (int) env('SESSION_INACTIVITY_MINUTES', 10),
        'sweeper_enabled'    => (bool) env('SESSION_SWEEPER_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Serper (Google Search API)
    |--------------------------------------------------------------------------
    |
    | Used by the lookup_price agent tool to fetch indicative retail prices.
    | Results are NEVER persisted — read once, returned, discarded.
    | Get an API key at https://serper.dev
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Enclivix CRM
    |--------------------------------------------------------------------------
    |
    | Used to push expenses to the internal CRM. Only active when
    | crm_sync_enabled is true for the user (enclivix.com accounts only).
    | ENCLIVIX_CRM_TOKEN is a secret — keep it in .env, never commit it.
    |
    */

    'enclivix_crm' => [
        'base_url' => env('ENCLIVIX_CRM_BASE_URL', 'https://crm.enclivix.com'),
        'token'    => env('ENCLIVIX_CRM_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Finvixy → Enclivix Stats Channel
    |--------------------------------------------------------------------------
    |
    | Separate, PII-FREE operational-stats channel to the Enclivix CRM. Pushes
    | counts, token usage, cost estimates and timestamps only — never names,
    | phone numbers, message text or receipt content. Reuses the existing
    | X-Finvixy-Token secret (services.enclivix_crm.token). STATS_PUSH_ENABLED
    | and an unset FINVIXY_STATS_URL both act as kill switches.
    |
    */

    'finvixy_stats' => [
        'url'         => env('FINVIXY_STATS_URL'),
        'enabled'     => (bool) env('STATS_PUSH_ENABLED', true),
        'account_cap' => (int) env('SERPER_ACCOUNT_CAP', 2500),
    ],

    'serper' => [
        'api_key'  => env('SERPER_API_KEY'),
        'endpoint' => env('SERPER_ENDPOINT', 'https://google.serper.dev/search'),
        'gl'       => env('SERPER_GL', 'za'),
        'location' => env('SERPER_LOCATION', 'Johannesburg, South Africa'),
        'num'      => (int) env('SERPER_NUM', 8),
    ],

];

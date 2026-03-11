<?php

/**
 * Production Hardening Configuration
 * 
 * This configuration file defines all production hardening settings for Finvixy.
 * It covers error handling, rate limiting, security, monitoring, and resilience.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Error Handling & Resilience
    |--------------------------------------------------------------------------
    */
    'error_handling' => [
        'retry_logic' => [
            'max_retries' => (int) env('HARDENING_MAX_RETRIES', 3),
            'initial_delay_ms' => (int) env('HARDENING_INITIAL_DELAY_MS', 100),
            'backoff_multiplier' => (float) env('HARDENING_BACKOFF_MULTIPLIER', 2.0),
        ],

        'circuit_breaker' => [
            'failure_threshold' => (int) env('HARDENING_CIRCUIT_FAILURE_THRESHOLD', 5),
            'success_threshold' => (int) env('HARDENING_CIRCUIT_SUCCESS_THRESHOLD', 2),
            'timeout_seconds' => (int) env('HARDENING_CIRCUIT_TIMEOUT', 60),
        ],

        'timeouts' => [
            'textract_timeout_seconds' => 30,
            'bedrock_timeout_seconds' => 60,
            'http_connect_timeout_seconds' => 10,
        ],

        'graceful_degradation' => [
            'use_fallback_on_textract_failure' => env('HARDENING_TEXTRACT_FALLBACK', false),
            'use_fallback_on_bedrock_failure' => env('HARDENING_BEDROCK_FALLBACK', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting & Throttling
    |--------------------------------------------------------------------------
    */
    'rate_limiting' => [
        'quotas' => [
            'textract_calls_per_day' => (int) env('QUOTA_TEXTRACT_CALLS', 1000),
            'bedrock_calls_per_day' => (int) env('QUOTA_BEDROCK_CALLS', 1000),
            'image_uploads_per_day' => (int) env('QUOTA_IMAGE_UPLOADS', 500),
            'whatsapp_messages_per_day' => (int) env('QUOTA_WHATSAPP_MESSAGES', 100),
        ],

        'throttling' => [
            'bedrock_rate_limit_ms' => 500,
            'textract_rate_limit_ms' => 100,
        ],

        'duplicate_prevention' => [
            'enabled' => env('HARDENING_DUPLICATE_PREVENTION', true),
            'check_invoice_number' => true,
            'check_vendor_amount_date' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Hardening
    |--------------------------------------------------------------------------
    */
    'security' => [
        'input_validation' => [
            'max_image_size_mb' => 10,
            'max_pdf_size_mb' => 50,
            'allowed_image_types' => ['image/jpeg', 'image/png', 'image/webp'],
            'allowed_document_types' => ['application/pdf'],
            'validate_image_header' => true,
            'validate_dimensions' => true,
        ],

        'malware_detection' => [
            'enabled' => env('HARDENING_MALWARE_DETECTION', true),
            'check_evil_patterns' => true,
        ],

        'encryption' => [
            'encrypt_sensitive_fields' => env('HARDENING_ENCRYPT_SENSITIVE', false),
            'sensitive_fields' => [
                'invoice_number',
                'vendor_name',
                'additional_fields',
            ],
        ],

        'audit_logging' => [
            'enabled' => env('HARDENING_AUDIT_LOGGING', true),
            'log_all_api_calls' => true,
            'log_failed_validations' => true,
            'log_security_events' => true,
            'retention_days' => (int) env('AUDIT_LOG_RETENTION_DAYS', 90),
        ],

        'whatsapp_webhook' => [
            'rate_limit_per_minute' => 30,
            'ip_whitelist' => env('WHATSAPP_IP_WHITELIST', ''),
            'verify_webhook_token' => true,
        ],

        'no_secrets_in_logs' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring & Observability
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'structured_logging' => [
            'enabled' => true,
            'format' => 'json',
            'min_log_level' => 'debug',
        ],

        'error_tracking' => [
            'enabled' => env('SENTRY_LARAVEL_DSN') ? true : false,
            'service' => 'sentry',
            'capture_breadcrumbs' => true,
        ],

        'performance_metrics' => [
            'enabled' => true,
            'track_api_latency' => true,
            'track_ocr_quality' => true,
            'track_parsing_quality' => true,
            'slow_request_threshold_ms' => 5000,
        ],

        'database_logging' => [
            'enabled' => env('APP_DEBUG', false),
            'log_slow_queries' => true,
            'slow_query_threshold_ms' => 1000,
        ],

        'ocr_monitoring' => [
            'confidence_threshold' => 0.50,
            'alert_on_low_confidence' => true,
            'track_confidence_scores' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Safeguards
    |--------------------------------------------------------------------------
    */
    'database' => [
        'soft_deletes' => [
            'enabled' => true,
            'models' => [
                'Expense',
                'ExpenseItem',
            ],
        ],

        'timestamps' => [
            'track_created_at' => true,
            'track_updated_at' => true,
            'track_last_processed_at' => true,
        ],

        'indexes' => [
            'status' => true,
            'organisation_id' => true,
            'user_id' => true,
            'date' => true,
            'is_duplicate' => true,
            'created_at' => true,
            'composite_indices' => [
                'organisation_id_status_date',
                'organisation_id_is_duplicate',
            ],
        ],

        'concurrent_processing' => [
            'enabled' => true,
            'max_concurrent_jobs' => 10,
            'lock_timeout_seconds' => 300,
        ],

        'backup_strategy' => [
            'enabled' => true,
            'backup_frequency' => 'daily',
            'retention_days' => 30,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration & Secrets
    |--------------------------------------------------------------------------
    */
    'configuration' => [
        'verify_required_env_vars' => [
            'AWS_ACCESS_KEY_ID',
            'AWS_SECRET_ACCESS_KEY',
            'AWS_DEFAULT_REGION',
            'AWS_TEXTRACT_REGION',
            'BEDROCK_EXPENSE_PARSER_AGENT_ID',
            'BEDROCK_EXPENSE_PARSER_ALIAS_ID',
            'DB_HOST',
            'DB_DATABASE',
            'DB_USERNAME',
        ],

        'secrets_management' => [
            'use_dotenv_vault' => env('DOTENV_VAULT_ENCRYPTION_KEY') ? true : false,
            'no_secrets_in_logs' => true,
            'credential_rotation_days' => 90,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    */
    'features' => [
        'enable_retry_logic' => env('HARDENING_ENABLE_RETRY', true),
        'enable_circuit_breaker' => env('HARDENING_ENABLE_CIRCUIT_BREAKER', true),
        'enable_rate_limiting' => env('HARDENING_ENABLE_RATE_LIMITING', true),
        'enable_audit_logging' => env('HARDENING_AUDIT_LOGGING', true),
        'enable_input_validation' => env('HARDENING_INPUT_VALIDATION', true),
        'enable_graceful_degradation' => env('HARDENING_GRACEFUL_DEGRADATION', true),
    ],

];

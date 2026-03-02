<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AWS Bedrock Configuration
    |--------------------------------------------------------------------------
    */

    'region' => env('BEDROCK_REGION', env('AWS_DEFAULT_REGION', 'us-east-1')),

    'agents' => [
        'expense_parser' => [
            'agent_id' => env('BEDROCK_EXPENSE_PARSER_AGENT_ID'),
            'alias_id' => env('BEDROCK_EXPENSE_PARSER_ALIAS_ID'),
        ],
    ],

];

<?php

return [

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'cognito' => [
        'user_pool_id' => env('AWS_COGNITO_USER_POOL_ID'),
        'client_id'    => env('AWS_COGNITO_CLIENT_ID'),
        'client_secret' => env('AWS_COGNITO_CLIENT_SECRET'),
        'region'       => env('AWS_COGNITO_REGION', 'us-east-1'),
        'jwk_url'      => env('AWS_COGNITO_JWK_URL'),
    ],

    'dynamodb' => [
        'region'              => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'messages_table'      => env('DYNAMODB_MESSAGES_TABLE', 'hiresphere-messages'),
        'conversations_table' => env('DYNAMODB_CONVERSATIONS_TABLE', 'hiresphere-conversations'),
    ],

    'stripe' => [
        'key'            => env('STRIPE_KEY'),
        'secret'         => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'daily' => [
        'api_key' => env('DAILY_API_KEY'),
        'api_url' => env('DAILY_API_URL', 'https://api.daily.co/v1'),
    ],

];

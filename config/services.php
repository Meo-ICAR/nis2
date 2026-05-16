<?php

return [
    /*
     * |--------------------------------------------------------------------------
     * | Third Party Services
     * |--------------------------------------------------------------------------
     * |
     * | This file is for storing the credentials for third party services such
     * | as Mailgun, Postmark, AWS and more. This file provides the de facto
     * | location for this type of information, allowing packages to have
     * | a conventional file to locate the various service credentials.
     * |
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
    'dell' => [
        'ip' => env('DELL_OME_IP'),
        'user' => env('DELL_OME_USER'),
        'pass' => env('DELL_OME_PASS'),
    ],
    'fossr' => [
        'auth_url' => env('FOSSR_AUTH_URL'),
        'gateway_url' => env('FOSSR_GATEWAY_URL'),
        'username' => env('FOSSR_USERNAME'),
        'password' => env('FOSSR_PASSWORD'),
        'client_id' => env('FOSSR_CLIENT_ID'),
        'client_secret' => env('FOSSR_CLIENT_SECRET'),
        'api_key' => env('FOSSR_API_KEY'),
    ],
    'oidcx' => [
        'base_url' => env('OIDC_BASE_URL'),
        'client_id' => env('OIDC_CLIENT_ID'),
        'client_secret' => env('OIDC_CLIENT_SECRET'),
        'redirect' => env('OIDC_REDIRECT_URI'),
    ],
    'wso2' => [
        'client_id' => env('OIDC_CLIENT_ID'),
        'client_secret' => env('OIDC_CLIENT_SECRET'),
        'token_url' => env('OIDC_PATH_URL') . '/oauth2/token',
        'scim_url' => env('OIDC_PATH_URL') . '/scim2/Users',
        'base_url' => env('OIDC_PATH_URL'),
    ],
    'oidc' => [
        'base_url' => env('OIDC_BASE_URL'),
        'client_id' => env('OIDC_CLIENT_ID'),
        'client_secret' => env('OIDC_CLIENT_SECRET'),
        'redirect' => env('OIDC_REDIRECT_URI'),
        'scopes' => ['openid', 'profile', 'email', 'internal_user_mgt_list'],
        // SCIM2 credentials for user management
        'scim_username' => env('OIDC_SCIM_USERNAME'),
        'scim_password' => env('OIDC_SCIM_PASSWORD'),
        // Optional: Enable JWT signature verification (default: false)
        // 'verify_jwt' => env('OIDC_VERIFY_JWT', false),
        // Optional: Provide a specific public key for JWT verification
        // If not provided, the key will be fetched from the OIDC provider's JWKS endpoint
        // 'jwt_public_key' => env('OIDC_JWT_PUBLIC_KEY'),
    ],
];

<?php
use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [

    /*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    */

    'api_path' => 'api',

    'api_domain' => null,

    /*
    |--------------------------------------------------------------------------
    | OpenAPI Export
    |--------------------------------------------------------------------------
    */

    'export_path' => 'api.json',

    'cache' => [
        'key' => 'scramble.openapi',
        'store' => 'file',
    ],

    'openapi_version' => \Dedoc\Scramble\OpenApiVersion::V3_1,

    /*
    |--------------------------------------------------------------------------
    | API Information
    |--------------------------------------------------------------------------
    */

    'info' => [
        'version' => env('API_VERSION', '1.0.0'),

        'description' => <<<'DESCRIPTION'
# ShopFlow API

ShopFlow is an E-Commerce REST API built with Laravel 12.

## Features

- User authentication using Laravel Sanctum.
- Product and category management.
- Product search, filtering, sorting, and pagination.
- Shopping cart management.
- Checkout and order processing.
- Order status and payment tracking.
- Admin-only management endpoints.

## Authentication

Protected endpoints require a Bearer Token.

Register or login to receive your access token, then use the Authorize button to authenticate requests.

## Base URL

Local development: http://127.0.0.1:8000/api
DESCRIPTION,
    ],

    /*
    |--------------------------------------------------------------------------
    | Documentation UI
    |--------------------------------------------------------------------------
    */

    'ui' => [
        'title' => 'ShopFlow API Documentation',
    ],

    'dev_tools' => [
        'enabled' => env(
            'SCRAMBLE_DEV_TOOLS',
            env('APP_DEBUG', false)
        ),
    ],

    'renderer' => 'elements',

    'renderers' => [

        'elements' => [
            'view' => 'scramble::docs',
            'theme' => 'light',
            'hideTryIt' => false,
            'hideSchemas' => false,
            'logo' => '',
            'tryItCredentialsPolicy' => 'include',
            'layout' => 'responsive',
            'router' => 'hash',
        ],

        'scalar' => [
            'view' => 'scramble::scalar',
            'cdn' => 'https://cdn.jsdelivr.net/npm/@scalar/api-reference',
            'theme' => 'laravel',
            'proxyUrl' => 'https://proxy.scalar.com',
            'darkMode' => false,
            'showDeveloperTools' => 'never',
            'agent' => ['disabled' => true],
            'credentials' => 'include',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | API Servers
    |--------------------------------------------------------------------------
    */

    'servers' => null,

    /*
    |--------------------------------------------------------------------------
    | Enum Documentation
    |--------------------------------------------------------------------------
    */

    'enum_cases_description_strategy' => 'description',

    'enum_cases_names_strategy' => false,

    /*
    |--------------------------------------------------------------------------
    | Query Parameters
    |--------------------------------------------------------------------------
    */

    'flatten_deep_query_parameters' => true,

    /*
    |--------------------------------------------------------------------------
    | Documentation Middleware
    |--------------------------------------------------------------------------
    */

    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],

    'extensions' => [],

    /*
    |--------------------------------------------------------------------------
    | API Authentication Documentation
    |--------------------------------------------------------------------------
    |
    | Automatically detects authentication middleware such as auth:sanctum
    | and documents Bearer Token authentication in OpenAPI.
    |
    */

    'security_strategy' =>
        \Dedoc\Scramble\SecurityDocumentation\MiddlewareAuthSecurityStrategy::class,

];

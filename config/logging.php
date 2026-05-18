<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => SyslogUdpHandler::class,
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
            ],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

        /*
        |----------------------------------------------------------------------
        | Canales dedicados de Facturación CFDI
        |----------------------------------------------------------------------
        | Plan: xdev/facturacion/PLAN_LOGS_FACTURACION.md
        | El canal 'cfdi' es un stack que agrupa los 6 sub-canales por área.
        | Cada sub-canal escribe a su propio archivo diario en storage/logs/cfdi/
        | con retención de 30 días (archivo). La persistencia en BD vive en
        | la tabla cfdi_timbrado_logs (retención configurable vía env).
        */
        'cfdi' => [
            'driver' => 'stack',
            'channels' => [
                'cfdi_timbrado',
                'cfdi_cancelacion',
                'cfdi_complemento_pago',
                'cfdi_retenciones',
                'cfdi_implocal',
                'cfdi_hub',
            ],
            'ignore_exceptions' => false,
        ],

        'cfdi_timbrado' => [
            'driver' => 'daily',
            'path' => storage_path('logs/cfdi/timbrado.log'),
            'level' => env('LOG_LEVEL_CFDI', 'info'),
            'days' => 30,
        ],

        'cfdi_cancelacion' => [
            'driver' => 'daily',
            'path' => storage_path('logs/cfdi/cancelacion.log'),
            'level' => env('LOG_LEVEL_CFDI', 'info'),
            'days' => 30,
        ],

        'cfdi_complemento_pago' => [
            'driver' => 'daily',
            'path' => storage_path('logs/cfdi/complemento_pago.log'),
            'level' => env('LOG_LEVEL_CFDI', 'info'),
            'days' => 30,
        ],

        'cfdi_retenciones' => [
            'driver' => 'daily',
            'path' => storage_path('logs/cfdi/retenciones.log'),
            'level' => env('LOG_LEVEL_CFDI', 'info'),
            'days' => 30,
        ],

        'cfdi_implocal' => [
            'driver' => 'daily',
            'path' => storage_path('logs/cfdi/implocal.log'),
            'level' => env('LOG_LEVEL_CFDI', 'info'),
            'days' => 30,
        ],

        'cfdi_hub' => [
            'driver' => 'daily',
            'path' => storage_path('logs/cfdi/hub.log'),
            'level' => env('LOG_LEVEL_CFDI', 'info'),
            'days' => 30,
        ],
    ],

];

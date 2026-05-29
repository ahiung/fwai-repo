<?php

declare(strict_types=1);

use Monolog\Level;

return [
    'app' => [
        'name' => 'Framework AI',
        'env' => $_ENV['APP_ENV'] ?? 'production',
        'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'timezone' => $_ENV['TIMEZONE'] ?? 'Asia/Jakarta',
        'key' => $_ENV['APP_KEY'] ?? '',
    ],
    'db' => [
        'driver' => $_ENV['DB_DRIVER'] ?? 'mysql',
        'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'port' => $_ENV['DB_PORT'] ?? '3306',
        'database' => $_ENV['DB_DATABASE'] ?? 'framework_ai',
        'username' => $_ENV['DB_USERNAME'] ?? 'root',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
        'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
        'collation' => $_ENV['DB_COLLATION'] ?? 'utf8mb4_unicode_ci',
        'prefix' => '',
    ],
    'twig' => [
        'paths' => [
            __DIR__ . '/../resources/views',
        ],
        'options' => [
            'cache' => false, // Set to folder path if caching is preferred in production
            'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'auto_reload' => true,
        ],
    ],
    'logger' => [
        'name' => 'app',
        'path' => __DIR__ . '/../var/log/app.log',
        'level' => Level::Debug,
    ],
];

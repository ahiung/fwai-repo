<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;

// Load Composer Autoloader
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
}

// Initialize Dotenv configuration
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad();
}

// Set global timezone
date_default_timezone_set($_ENV['TIMEZONE'] ?? 'Asia/Jakarta');

// Build PHP-DI Container
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../config/dependencies.php');

$container = $containerBuilder->build();

// Boot Database Eloquent Capsule
$container->get(Capsule::class);

// Create Slim Application with Container
AppFactory::setContainer($container);
$app = AppFactory::create();

// Dynamic Base Path Detection (for XAMPP subfolders support)
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$basePath = '';

if (strpos($scriptName, '/index.php') !== false) {
    // If the browser URL explicitly contains '/public/'
    if (strpos($requestUri, '/public/') !== false) {
        $basePath = str_replace('/index.php', '', $scriptName);
    } else {
        $basePath = str_replace('/public/index.php', '', $scriptName);
    }
} else {
    $basePath = rtrim(dirname($scriptName), '/');
}
$basePath = rtrim($basePath, '/');
$app->setBasePath($basePath);

// Register dynamic base_path and session information globally to Twig
$twig = $container->get(Twig::class);
$twig->getEnvironment()->addGlobal('base_path', $basePath);

// Register Session to Twig globally (for user profile & layout info)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$twig->getEnvironment()->addGlobal('session', $_SESSION);

// Register routing and body parsing middlewares
$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();

// Register Twig Middleware
$app->add(TwigMiddleware::create($app, $twig));

// Error Middleware settings
$settings = $container->get('settings');
$displayErrorDetails = $settings['app']['debug'];

$errorMiddleware = $app->addErrorMiddleware(
    $displayErrorDetails,
    true, // Log errors
    true  // Log error details
);

// Centralized Custom Error Handler registration (Part 3)
$customErrorHandlerPath = __DIR__ . '/../app/Exceptions/ErrorHandler.php';
if (file_exists($customErrorHandlerPath)) {
    $errorHandler = new \App\Exceptions\ErrorHandler(
        $app->getResponseFactory(),
        $container->get(Slim\Views\Twig::class),
        $container->get(Slim\Flash\Messages::class),
        $container->get(Monolog\Logger::class)
    );
    $errorMiddleware->setDefaultErrorHandler($errorHandler);
}

// Load Application Routes if routes.php exists, else show landing page
$routesPath = __DIR__ . '/../config/routes.php';
if (file_exists($routesPath)) {
    (require $routesPath)($app);
} else {
    // Temporarily register a test route for validation
    $app->get('/', function ($request, $response) {
        $response->getBody()->write("<h1>Framework AI Core Engine Initialized Successfully!</h1><p>Part 1 is working. Vendor assets and composer dependencies are configured.</p>");
        return $response;
    });
}

$app->run();

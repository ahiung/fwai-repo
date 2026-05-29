<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Slim\Views\Twig;
use Slim\Flash\Messages;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container as IlluminateContainer;

return [
    // Bind settings configuration
    'settings' => function () {
        return require __DIR__ . '/settings.php';
    },

    // Eloquent Capsule Manager Setup
    Capsule::class => function (ContainerInterface $container) {
        $settings = $container->get('settings')['db'];
        
        $capsule = new Capsule();
        $capsule->addConnection($settings);
        $capsule->setEventDispatcher(new Dispatcher(new IlluminateContainer()));
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        
        return $capsule;
    },

    // Twig View Integration
    Twig::class => function (ContainerInterface $container) {
        $settings = $container->get('settings')['twig'];
        $twig = Twig::create($settings['paths'], $settings['options']);
        
        // Register standard extensions if needed
        $environment = $twig->getEnvironment();
        
        // base_path global variable placeholder (will be overwritten dynamically in index.php)
        $environment->addGlobal('base_path', '');
        
        // Register flash messages to twig
        $flash = $container->get(Messages::class);
        $environment->addGlobal('flash', $flash);
        
        // Register custom Twig Auth Extension for 'can' permission checks
        $twig->addExtension(new \App\Extensions\TwigAuthExtension());
        
        // Register global PHP helper functions to Twig
        $environment->addFunction(new \Twig\TwigFunction('format_rupiah', 'format_rupiah'));
        $environment->addFunction(new \Twig\TwigFunction('format_tanggal', 'format_tanggal'));
        $environment->addFunction(new \Twig\TwigFunction('generate_kode', 'generate_kode'));
        
        return $twig;
    },

    // Slim Flash Messages
    Messages::class => function () {
        // Ensure session is started before using flash messages
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return new Messages();
    },

    // Monolog Logger Integration
    Logger::class => function (ContainerInterface $container) {
        $settings = $container->get('settings')['logger'];
        
        $logger = new Logger($settings['name']);
        
        // Ensure log directory exists
        $logDir = dirname($settings['path']);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logger->pushHandler(new StreamHandler($settings['path'], $settings['level']));
        
        return $logger;
    },

    // In PHP-DI, standard classes like services (e.g. AuditService, UserService) 
    // are auto-wired automatically using reflection, so they do not need 
    // to be explicitly registered here unless they need special configurations.
];

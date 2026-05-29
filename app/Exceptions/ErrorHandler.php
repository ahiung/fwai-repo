<?php

declare(strict_types=1);

namespace App\Exceptions;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Interfaces\ErrorHandlerInterface;
use Slim\Views\Twig;
use Slim\Flash\Messages;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Centrally manages exceptions and system errors across the application.
 */
class ErrorHandler implements ErrorHandlerInterface
{
    private ResponseFactoryInterface $responseFactory;
    private Twig $view;
    private Messages $flash;
    private LoggerInterface $logger;

    /**
     * ErrorHandler constructor.
     *
     * @param ResponseFactoryInterface $responseFactory
     * @param Twig $view
     * @param Messages $flash
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResponseFactoryInterface $responseFactory,
        Twig $view,
        Messages $flash,
        LoggerInterface $logger
    ) {
        $this->responseFactory = $responseFactory;
        $this->view = $view;
        $this->flash = $flash;
        $this->logger = $logger;
    }

    /**
     * Handles exceptions and maps them to JSON or redirect responses.
     */
    public function __invoke(
        Request $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ): Response {
        // Log errors to application log if enabled
        if ($logErrors) {
            $this->logger->error($exception->getMessage(), [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'uri' => (string)$request->getUri(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);
        }

        // Determine if request expects a JSON (AJAX) response
        $isAjax = $this->isAjaxRequest($request);

        // Detect base path dynamically (for subdirectories)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = '';
        if (strpos($scriptName, '/index.php') !== false) {
            $basePath = str_replace('/public/index.php', '', $scriptName);
        } else {
            $basePath = rtrim(dirname($scriptName), '/');
        }
        $basePath = rtrim($basePath, '/');

        // 1. Process custom domain exceptions (AppException and subclasses)
        if ($exception instanceof AppException) {
            $statusCode = $exception->getCode();
            // Validate code matches standard range
            if ($statusCode < 400 || $statusCode > 599) {
                $statusCode = 500;
            }

            if ($isAjax) {
                $response = $this->responseFactory->createResponse($statusCode);
                $payload = [
                    'status' => 'error',
                    'message' => $exception->getMessage()
                ];

                if ($exception instanceof ValidationException) {
                    $payload['errors'] = $exception->getErrors();
                }

                $response->getBody()->write((string)json_encode($payload));
                return $response->withHeader('Content-Type', 'application/json');
            } else {
                $response = $this->responseFactory->createResponse(302);
                
                // If it is a validation error, store validation messages in session
                if ($exception instanceof ValidationException) {
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }
                    $_SESSION['errors'] = $exception->getErrors();
                    $_SESSION['old'] = $request->getParsedBody();
                }

                $this->flash->addMessage('error', $exception->getMessage());
                
                // Redirect back to Referer or default dashboard path
                $referer = $request->getHeaderLine('Referer');
                $redirectUrl = !empty($referer) ? $referer : ($basePath . '/dashboard');
                
                return $response->withHeader('Location', $redirectUrl);
            }
        }

        // 2. Process system exceptions or third-party exceptions
        $statusCode = 500;
        
        // Map common routing exceptions
        $className = get_class($exception);
        if (strpos($className, 'HttpNotFoundException') !== false) {
            $statusCode = 404;
            $errorMessage = "Halaman atau tautan tidak ditemukan.";
        } elseif (strpos($className, 'HttpForbiddenException') !== false) {
            $statusCode = 403;
            $errorMessage = "Akses halaman ini ditolak.";
        } else {
            $errorMessage = $displayErrorDetails ? $exception->getMessage() : "Terjadi kesalahan internal pada server.";
            if (method_exists($exception, 'getCode')) {
                $code = $exception->getCode();
                if ($code >= 400 && $code <= 599) {
                    $statusCode = $code;
                }
            }
        }

        if ($isAjax) {
            $response = $this->responseFactory->createResponse($statusCode);
            $payload = [
                'status' => 'error',
                'message' => $errorMessage
            ];
            
            if ($displayErrorDetails) {
                $payload['details'] = [
                    'class' => get_class($exception),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString()
                ];
            }

            $response->getBody()->write((string)json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            // Render premium static HTML for client when debug details are disabled
            if (!$displayErrorDetails) {
                $response = $this->responseFactory->createResponse($statusCode);
                try {
                    // Try to render standard error page template from views
                    return $this->view->render($response, 'errors/error.twig', [
                        'code' => $statusCode,
                        'message' => $errorMessage,
                        'base_path' => $basePath
                    ]);
                } catch (Throwable $t) {
                    // Direct high-quality premium HTML fallback
                    $response->getBody()->write($this->getPremiumErrorHtml($statusCode, $errorMessage, $basePath));
                    return $response;
                }
            } else {
                // In development mode, allow default PHP/Slim error outputs for debugging
                throw $exception;
            }
        }
    }

    /**
     * Identify if request requires JSON response
     */
    private function isAjaxRequest(Request $request): bool
    {
        $requestedWith = $request->getHeaderLine('X-Requested-With');
        $accept = $request->getHeaderLine('Accept');
        
        return $requestedWith === 'XMLHttpRequest' || strpos($accept, 'application/json') !== false;
    }

    /**
     * Fallback Premium HTML representation for errors
     */
    private function getPremiumErrorHtml(int $code, string $message, string $basePath): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error {$code} - Framework AI</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Outfit:wght@800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            color: #1f2937;
        }
        .container {
            text-align: center;
            background: white;
            padding: 3rem;
            border-radius: 2rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            max-width: 450px;
            border: 1px solid #f3f4f6;
        }
        h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 6rem;
            margin: 0;
            background: linear-gradient(to right, #2563eb, #4f46e5);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
        }
        h2 {
            font-size: 1.25rem;
            margin: 1.5rem 0 0.5rem 0;
            font-weight: 600;
        }
        p {
            color: #6b7280;
            font-size: 0.875rem;
            margin: 0 0 2rem 0;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(to right, #2563eb, #4f46e5);
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 1rem;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.875rem;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
            transition: all 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>{$code}</h1>
        <h2>Oops! Terjadi Masalah</h2>
        <p>{$message}</p>
        <a href="javascript:history.back()" class="btn">Kembali ke Halaman Sebelumnya</a>
    </div>
</body>
</html>
HTML;
    }
}

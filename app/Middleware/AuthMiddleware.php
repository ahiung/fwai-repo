<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Routing\RouteContext;
use Slim\Flash\Messages;
use Slim\Psr7\Response as SlimResponse;

/**
 * Authentication Middleware to check if user session exists.
 */
class AuthMiddleware implements MiddlewareInterface
{
    /**
     * @var Messages
     */
    private Messages $flash;

    /**
     * AuthMiddleware constructor.
     *
     * @param Messages $flash
     */
    public function __construct(Messages $flash)
    {
        $this->flash = $flash;
    }

    /**
     * Process request and check session.
     */
    public function process(Request $request, Handler $handler): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user'])) {
            // Identify if request expects a JSON (AJAX) response
            $requestedWith = $request->getHeaderLine('X-Requested-With');
            $accept = $request->getHeaderLine('Accept');
            $isAjax = $requestedWith === 'XMLHttpRequest' || strpos($accept, 'application/json') !== false;

            if ($isAjax) {
                $response = new SlimResponse();
                $response->getBody()->write((string)json_encode([
                    'status' => 'error',
                    'message' => 'Sesi Anda telah berakhir. Silakan masuk kembali.'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
            }

            // Web redirect to login
            $routeContext = RouteContext::fromRequest($request);
            $basePath = $routeContext->getBasePath();

            $this->flash->addMessage('error', 'Silakan masuk terlebih dahulu.');

            $response = new SlimResponse();
            return $response->withHeader('Location', $basePath . '/login')->withStatus(302);
        }

        return $handler->handle($request);
    }
}

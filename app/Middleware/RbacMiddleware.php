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
 * Role-Based Access Control Middleware to enforce granular permissions.
 */
class RbacMiddleware implements MiddlewareInterface
{
    /**
     * @var Messages
     */
    private Messages $flash;

    /**
     * @var string
     */
    private string $permission;

    /**
     * RbacMiddleware constructor.
     *
     * @param Messages $flash
     * @param string $permission Granular permission string required
     */
    public function __construct(Messages $flash, string $permission)
    {
        $this->flash = $flash;
        $this->permission = $permission;
    }

    /**
     * Process request and check permissions.
     */
    public function process(Request $request, Handler $handler): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $user = $_SESSION['user'] ?? null;
        
        // Identify if request expects a JSON (AJAX) response
        $requestedWith = $request->getHeaderLine('X-Requested-With');
        $accept = $request->getHeaderLine('Accept');
        $isAjax = $requestedWith === 'XMLHttpRequest' || strpos($accept, 'application/json') !== false;

        if (!$user) {
            if ($isAjax) {
                $response = new SlimResponse();
                $response->getBody()->write((string)json_encode([
                    'status' => 'error',
                    'message' => 'Sesi Anda telah berakhir. Silakan masuk kembali.'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
            }

            $routeContext = RouteContext::fromRequest($request);
            $basePath = $routeContext->getBasePath();
            
            $response = new SlimResponse();
            return $response->withHeader('Location', $basePath . '/login')->withStatus(302);
        }

        // Superadmin bypass (role_id 1)
        $isSuperadmin = ((int)$user['role_id'] === 1 || ($user['role_name'] ?? '') === 'Superadmin');
        
        // Get user permissions loaded into session on authentication
        $permissions = $_SESSION['user_permissions'] ?? [];

        if (!$isSuperadmin && !in_array($this->permission, $permissions)) {
            if ($isAjax) {
                $response = new SlimResponse();
                $response->getBody()->write((string)json_encode([
                    'status' => 'error',
                    'message' => 'Akses ditolak. Anda tidak memiliki izin untuk melakukan tindakan ini.'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }

            $routeContext = RouteContext::fromRequest($request);
            $basePath = $routeContext->getBasePath();

            $this->flash->addMessage('error', 'Akses ditolak: Anda tidak memiliki hak akses yang diperlukan.');

            $response = new SlimResponse();
            return $response->withHeader('Location', $basePath . '/dashboard')->withStatus(302);
        }

        return $handler->handle($request);
    }
}

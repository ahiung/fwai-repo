<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Slim\Flash\Messages;
use App\Services\AuthService;
use App\Services\AuditService;
use App\Requests\Auth\LoginRequest;
use App\Exceptions\ValidationException;

/**
 * Controller to handle web login, logout, and session lifecycle.
 */
class AuthController
{
    private Twig $view;
    private Messages $flash;
    private AuthService $authService;
    private AuditService $auditService;

    /**
     * AuthController constructor.
     */
    public function __construct(
        Twig $view,
        Messages $flash,
        AuthService $authService,
        AuditService $auditService
    ) {
        $this->view = $view;
        $this->flash = $flash;
        $this->authService = $authService;
        $this->auditService = $auditService;
    }

    /**
     * Render the beautiful login form.
     */
    public function loginForm(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // If user session is already set, redirect directly to dashboard
        if (isset($_SESSION['user'])) {
            $routeContext = \Slim\Routing\RouteContext::fromRequest($request);
            $basePath = $routeContext->getBasePath();
            return $response->withHeader('Location', $basePath . '/dashboard')->withStatus(302);
        }

        // Collect old inputs and errors stored by exceptions
        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);

        return $this->view->render($response, 'auth/login.twig', [
            'errors' => $errors,
            'old' => $old
        ]);
    }

    /**
     * Authenticate post credentials.
     */
    public function login(Request $request, Response $response): Response
    {
        $routeContext = \Slim\Routing\RouteContext::fromRequest($request);
        $basePath = $routeContext->getBasePath();

        $parsedBody = $request->getParsedBody() ?: [];

        try {
            // 1. Validate inputs
            $loginRequest = new LoginRequest();
            $sanitized = $loginRequest->validate($parsedBody);

            // 2. Authenticate credentials
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $this->authService->authenticate(
                $sanitized['username'],
                $sanitized['password'],
                $ipAddress
            );

            $this->flash->addMessage('success', 'Selamat datang kembali di Framework AI!');
            
            return $response->withHeader('Location', $basePath . '/dashboard')->withStatus(302);

        } catch (ValidationException $e) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['errors'] = $e->getErrors();
            $_SESSION['old'] = $parsedBody;
            
            $this->flash->addMessage('error', $e->getMessage());
            
            return $response->withHeader('Location', $basePath . '/login')->withStatus(302);
        } catch (\Exception $e) {
            $this->flash->addMessage('error', $e->getMessage());
            return $response->withHeader('Location', $basePath . '/login')->withStatus(302);
        }
    }

    /**
     * Terminate user session.
     */
    public function logout(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $routeContext = \Slim\Routing\RouteContext::fromRequest($request);
        $basePath = $routeContext->getBasePath();

        $user = $_SESSION['user'] ?? null;

        if ($user) {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            // Log security audit for logout
            $this->auditService->log(
                (int)$user['id'],
                'LOGOUT',
                'Auth',
                "Berhasil keluar: User '{$user['username']}' keluar dari sistem secara normal.",
                $ipAddress
            );
        }

        // Clean session variables
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();

        return $response->withHeader('Location', $basePath . '/login')->withStatus(302);
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Slim\Flash\Messages;
use App\Services\ProfileService;
use App\Requests\Profile\UpdateProfileRequest;
use App\Exceptions\ValidationException;

/**
 * Controller to handle authenticated profile viewport and update operations.
 */
class ProfileController
{
    private Twig $view;
    private Messages $flash;
    private ProfileService $profileService;

    /**
     * ProfileController constructor.
     */
    public function __construct(
        Twig $view,
        Messages $flash,
        ProfileService $profileService
    ) {
        $this->view = $view;
        $this->flash = $flash;
        $this->profileService = $profileService;
    }

    /**
     * Display profile form page.
     */
    public function index(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userSession = $_SESSION['user'] ?? null;
        if (!$userSession) {
            $basePath = (string)$request->getAttribute('basePath', '');
            return $response->withHeader('Location', $basePath . '/login')->withStatus(302);
        }

        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);

        // Fetch latest database state
        $user = \App\Models\User::find((int)$userSession['id']);

        return $this->view->render($response, 'profile/index.twig', [
            'current_route' => 'profile',
            'user' => $user,
            'errors' => $errors,
            'old' => $old
        ]);
    }

    /**
     * Handle profile update.
     */
    public function update(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userSession = $_SESSION['user'] ?? null;
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = '';
        if (strpos($scriptName, '/index.php') !== false) {
            $basePath = str_replace('/public/index.php', '', $scriptName);
        } else {
            $basePath = rtrim(dirname($scriptName), '/');
        }
        $basePath = rtrim($basePath, '/');

        if (!$userSession) {
            return $response->withHeader('Location', $basePath . '/login')->withStatus(302);
        }

        $parsedBody = $request->getParsedBody() ?: [];
        $userId = (int)$userSession['id'];

        try {
            // 1. Validate form fields
            $profileRequest = new UpdateProfileRequest();
            $sanitized = $profileRequest->validate($parsedBody, $userId);

            // 2. Perform updates
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $this->profileService->update($userId, $sanitized, $ipAddress);

            $this->flash->addMessage('success', 'Profil Anda berhasil diperbarui.');
            return $response->withHeader('Location', $basePath . '/profile')->withStatus(302);

        } catch (ValidationException $e) {
            $_SESSION['errors'] = $e->getErrors();
            $_SESSION['old'] = $parsedBody;
            
            $this->flash->addMessage('error', $e->getMessage());
            return $response->withHeader('Location', $basePath . '/profile')->withStatus(302);
        } catch (\Exception $e) {
            $this->flash->addMessage('error', $e->getMessage());
            return $response->withHeader('Location', $basePath . '/profile')->withStatus(302);
        }
    }
}

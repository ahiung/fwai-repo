<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Slim\Flash\Messages;
use App\Services\UserService;
use App\Models\Role;
use App\Models\User;
use App\Requests\User\StoreUserRequest;
use App\Requests\User\UpdateUserRequest;
use App\Exceptions\ValidationException;
use Illuminate\Pagination\Paginator;

/**
 * Controller to manage User Accounts CRUD operability and pagination.
 */
class UserController
{
    private Twig $view;
    private Messages $flash;
    private UserService $userService;

    /**
     * UserController constructor.
     */
    public function __construct(
        Twig $view,
        Messages $flash,
        UserService $userService
    ) {
        $this->view = $view;
        $this->flash = $flash;
        $this->userService = $userService;
    }

    /**
     * Display all users with pagination.
     */
    public function index(Request $request, Response $response): Response
    {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = '';
        if (strpos($scriptName, '/index.php') !== false) {
            $basePath = str_replace('/public/index.php', '', $scriptName);
        } else {
            $basePath = rtrim(dirname($scriptName), '/');
        }
        $basePath = rtrim($basePath, '/');

        // Resolve Page Number from Query Params
        $queryParams = $request->getQueryParams();
        $page = isset($queryParams['page']) ? (int)$queryParams['page'] : 1;

        // Register custom resolvers for Illuminate Pagination
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        Paginator::currentPathResolver(function () use ($basePath) {
            return $basePath . '/users';
        });

        // Paginate users (10 entries per page) with eager-loaded role
        $usersPaginator = User::with('role')->paginate(10);

        return $this->view->render($response, 'users/index.twig', [
            'current_route' => 'users',
            'users' => $usersPaginator->items(),
            'paginator' => $usersPaginator
        ]);
    }

    /**
     * Show form to add user.
     */
    public function create(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);

        // Fetch all available roles for choices selection
        $roles = Role::all()->toArray();

        return $this->view->render($response, 'users/add.twig', [
            'current_route' => 'users',
            'roles' => $roles,
            'errors' => $errors,
            'old' => $old
        ]);
    }

    /**
     * Save new user.
     */
    public function store(Request $request, Response $response): Response
    {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = '';
        if (strpos($scriptName, '/index.php') !== false) {
            $basePath = str_replace('/public/index.php', '', $scriptName);
        } else {
            $basePath = rtrim(dirname($scriptName), '/');
        }
        $basePath = rtrim($basePath, '/');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $operatorId = (int)($_SESSION['user']['id'] ?? 0);
        $parsedBody = $request->getParsedBody() ?: [];

        try {
            $storeRequest = new StoreUserRequest();
            $sanitized = $storeRequest->validate($parsedBody);

            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $this->userService->store($sanitized, $operatorId, $ipAddress);

            $this->flash->addMessage('success', 'User baru berhasil disimpan.');
            return $response->withHeader('Location', $basePath . '/users')->withStatus(302);

        } catch (ValidationException $e) {
            $_SESSION['errors'] = $e->getErrors();
            $_SESSION['old'] = $parsedBody;
            $this->flash->addMessage('error', $e->getMessage());
            return $response->withHeader('Location', $basePath . '/users/add')->withStatus(302);
        } catch (\Exception $e) {
            $this->flash->addMessage('error', $e->getMessage());
            return $response->withHeader('Location', $basePath . '/users/add')->withStatus(302);
        }
    }

    /**
     * Show form to edit user.
     */
    public function edit(Request $request, Response $response, array $args): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $id = (int)($args['id'] ?? 0);
        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);

        try {
            $user = $this->userService->findById($id);
            $roles = Role::all()->toArray();

            return $this->view->render($response, 'users/edit.twig', [
                'current_route' => 'users',
                'user' => $user,
                'roles' => $roles,
                'errors' => $errors,
                'old' => $old
            ]);
        } catch (\Exception $e) {
            $basePath = (string)$request->getAttribute('basePath', '');
            $this->flash->addMessage('error', $e->getMessage());
            return $response->withHeader('Location', $basePath . '/users')->withStatus(302);
        }
    }

    /**
     * Save edited user.
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int)($args['id'] ?? 0);
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = '';
        if (strpos($scriptName, '/index.php') !== false) {
            $basePath = str_replace('/public/index.php', '', $scriptName);
        } else {
            $basePath = rtrim(dirname($scriptName), '/');
        }
        $basePath = rtrim($basePath, '/');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $operatorId = (int)($_SESSION['user']['id'] ?? 0);
        $parsedBody = $request->getParsedBody() ?: [];

        try {
            $updateRequest = new UpdateUserRequest();
            $sanitized = $updateRequest->validate($parsedBody, $id);

            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $this->userService->update($id, $sanitized, $operatorId, $ipAddress);

            $this->flash->addMessage('success', 'Data user berhasil diperbarui.');
            return $response->withHeader('Location', $basePath . '/users')->withStatus(302);

        } catch (ValidationException $e) {
            $_SESSION['errors'] = $e->getErrors();
            $_SESSION['old'] = $parsedBody;
            $this->flash->addMessage('error', $e->getMessage());
            return $response->withHeader('Location', $basePath . "/users/edit/{$id}")->withStatus(302);
        } catch (\Exception $e) {
            $this->flash->addMessage('error', $e->getMessage());
            return $response->withHeader('Location', $basePath . "/users/edit/{$id}")->withStatus(302);
        }
    }

    /**
     * Delete existing user.
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int)($args['id'] ?? 0);
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = '';
        if (strpos($scriptName, '/index.php') !== false) {
            $basePath = str_replace('/public/index.php', '', $scriptName);
        } else {
            $basePath = rtrim(dirname($scriptName), '/');
        }
        $basePath = rtrim($basePath, '/');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $operatorId = (int)($_SESSION['user']['id'] ?? 0);

        try {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $this->userService->delete($id, $operatorId, $ipAddress);

            $this->flash->addMessage('success', 'User berhasil dihapus.');
        } catch (\Exception $e) {
            $this->flash->addMessage('error', $e->getMessage());
        }

        return $response->withHeader('Location', $basePath . '/users')->withStatus(302);
    }
}

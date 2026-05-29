<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Slim\Flash\Messages;
use App\Services\RoleService;
use App\Models\Permission;
use App\Requests\Role\StoreRoleRequest;
use App\Requests\Role\UpdateRoleRequest;
use App\Exceptions\ValidationException;

/**
 * Controller to manage Peran (Roles) web views and backend operations.
 */
class RoleController
{
    private Twig $view;
    private Messages $flash;
    private RoleService $roleService;

    /**
     * RoleController constructor.
     */
    public function __construct(
        Twig $view,
        Messages $flash,
        RoleService $roleService
    ) {
        $this->view = $view;
        $this->flash = $flash;
        $this->roleService = $roleService;
    }

    /**
     * Display all roles list page.
     */
    public function index(Request $request, Response $response): Response
    {
        $roles = $this->roleService->getAll();

        return $this->view->render($response, 'roles/index.twig', [
            'current_route' => 'roles',
            'roles' => $roles
        ]);
    }

    /**
     * Show form to add role.
     */
    public function create(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);

        // Fetch all permissions grouped by their category or simply all permissions
        $permissions = Permission::all();

        return $this->view->render($response, 'roles/add.twig', [
            'current_route' => 'roles',
            'permissions' => $permissions,
            'errors' => $errors,
            'old' => $old
        ]);
    }

    /**
     * Save new role.
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
            $storeRequest = new StoreRoleRequest();
            $sanitized = $storeRequest->validate($parsedBody);

            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $this->roleService->store($sanitized, $operatorId, $ipAddress);

            $this->flash->addMessage('success', 'Peran baru berhasil disimpan.');
            return $response->withHeader('Location', $basePath . '/roles')->withStatus(302);

        } catch (ValidationException $e) {
            $_SESSION['errors'] = $e->getErrors();
            $_SESSION['old'] = $parsedBody;
            $this->flash->addMessage('error', $e->getMessage());
            return $response->withHeader('Location', $basePath . '/roles/add')->withStatus(302);
        } catch (\Exception $e) {
            $this->flash->addMessage('error', $e->getMessage());
            return $response->withHeader('Location', $basePath . '/roles/add')->withStatus(302);
        }
    }

    /**
     * Show form to edit role.
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
            $role = $this->roleService->findById($id);
            $permissions = Permission::all();
            
            // Pluck permission IDs associated with this role
            $rolePermissionIds = $role->permissions->pluck('id')->toArray();

            return $this->view->render($response, 'roles/edit.twig', [
                'current_route' => 'roles',
                'role' => $role,
                'permissions' => $permissions,
                'role_permission_ids' => $rolePermissionIds,
                'errors' => $errors,
                'old' => $old
            ]);
        } catch (\Exception $e) {
            $basePath = (string)$request->getAttribute('basePath', '');
            $this->flash->addMessage('error', $e->getMessage());
            return $response->withHeader('Location', $basePath . '/roles')->withStatus(302);
        }
    }

    /**
     * Save edited role.
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
            $updateRequest = new UpdateRoleRequest();
            $sanitized = $updateRequest->validate($parsedBody, $id);

            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $this->roleService->update($id, $sanitized, $operatorId, $ipAddress);

            $this->flash->addMessage('success', 'Peran berhasil diperbarui.');
            return $response->withHeader('Location', $basePath . '/roles')->withStatus(302);

        } catch (ValidationException $e) {
            $_SESSION['errors'] = $e->getErrors();
            $_SESSION['old'] = $parsedBody;
            $this->flash->addMessage('error', $e->getMessage());
            return $response->withHeader('Location', $basePath . "/roles/edit/{$id}")->withStatus(302);
        } catch (\Exception $e) {
            $this->flash->addMessage('error', $e->getMessage());
            return $response->withHeader('Location', $basePath . "/roles/edit/{$id}")->withStatus(302);
        }
    }

    /**
     * Delete existing role.
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
            $this->roleService->delete($id, $operatorId, $ipAddress);

            $this->flash->addMessage('success', 'Peran berhasil dihapus.');
        } catch (\Exception $e) {
            $this->flash->addMessage('error', $e->getMessage());
        }

        return $response->withHeader('Location', $basePath . '/roles')->withStatus(302);
    }
}

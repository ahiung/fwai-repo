<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Slim\Flash\Messages;
use App\Services\KantorCabangService;
use App\Services\UserService;
use App\Models\User;
use App\Models\KantorCabang;
use App\Requests\KantorCabang\StoreKantorCabangRequest;
use App\Requests\KantorCabang\UpdateKantorCabangRequest;
use App\Exceptions\ValidationException;
use Illuminate\Pagination\Paginator;

/**
 * Controller to manage Kantor Cabang (Branch Offices) CRUD operability and pagination.
 */
class KantorCabangController
{
    private Twig $view;
    private Messages $flash;
    private KantorCabangService $kantorCabangService;
    private UserService $userService;

    /**
     * KantorCabangController constructor.
     */
    public function __construct(
        Twig $view,
        Messages $flash,
        KantorCabangService $kantorCabangService,
        UserService $userService
    ) {
        $this->view = $view;
        $this->flash = $flash;
        $this->kantorCabangService = $kantorCabangService;
        $this->userService = $userService;
    }

    /**
     * Display all branch offices with pagination.
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

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $operatorId = (int)($_SESSION['user']['id'] ?? 0);
        $operator = User::find($operatorId);

        // Register custom resolvers for Illuminate Pagination
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        Paginator::currentPathResolver(function () use ($basePath) {
            return $basePath . '/kantor-cabang';
        });

        // Query with eager-loaded manager and apply Data Scope
        $query = KantorCabang::with('manager');
        if ($operator && $operator->role_id !== 1) {
            $query->where('manager_id', $operatorId);
        }

        // Paginate offices (10 entries per page)
        $officesPaginator = $query->paginate(10);

        return $this->view->render($response, 'kantor_cabang/list.twig', [
            'current_route' => 'kantor_cabang',
            'offices' => $officesPaginator->items(),
            'paginator' => $officesPaginator
        ]);
    }

    /**
     * Show form to add branch office.
     */
    public function create(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);

        // Fetch all users to choose as manager
        $users = $this->userService->getAll();

        return $this->view->render($response, 'kantor_cabang/add.twig', [
            'current_route' => 'kantor_cabang',
            'users' => $users,
            'errors' => $errors,
            'old' => $old
        ]);
    }

    /**
     * Save new branch office.
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
            $storeRequest = new StoreKantorCabangRequest();
            $sanitized = $storeRequest->validate($parsedBody);

            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $this->kantorCabangService->store($sanitized, $operatorId, $ipAddress);

            $this->flash->addMessage('success', 'Kantor cabang baru berhasil disimpan.');
            return $response->withHeader('Location', $basePath . '/kantor-cabang')->withStatus(302);

        } catch (ValidationException $e) {
            $_SESSION['errors'] = $e->getErrors();
            $_SESSION['old'] = $parsedBody;
            $this->flash->addMessage('error', $e->getMessage());
            return $response->withHeader('Location', $basePath . '/kantor-cabang/add')->withStatus(302);
        } catch (\Exception $e) {
            $this->flash->addMessage('error', $e->getMessage());
            return $response->withHeader('Location', $basePath . '/kantor-cabang/add')->withStatus(302);
        }
    }

    /**
     * Show detail page for a branch office (view).
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $id = (int)($args['id'] ?? 0);
        $operatorId = (int)($_SESSION['user']['id'] ?? 0);

        try {
            $office = $this->kantorCabangService->findById($id, $operatorId);

            return $this->view->render($response, 'kantor_cabang/view.twig', [
                'current_route' => 'kantor_cabang',
                'office' => $office
            ]);
        } catch (\Exception $e) {
            $basePath = (string)$request->getAttribute('basePath', '');
            if (empty($basePath)) {
                $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
                if (strpos($scriptName, '/index.php') !== false) {
                    $basePath = str_replace('/public/index.php', '', $scriptName);
                } else {
                    $basePath = rtrim(dirname($scriptName), '/');
                }
            }
            $basePath = rtrim($basePath, '/');
            $this->flash->addMessage('error', $e->getMessage());
            return $response->withHeader('Location', $basePath . '/kantor-cabang')->withStatus(302);
        }
    }

    /**
     * Show form to edit branch office.
     */
    public function edit(Request $request, Response $response, array $args): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $id = (int)($args['id'] ?? 0);
        $operatorId = (int)($_SESSION['user']['id'] ?? 0);
        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);

        try {
            $office = $this->kantorCabangService->findById($id, $operatorId);
            $users = $this->userService->getAll();

            return $this->view->render($response, 'kantor_cabang/edit.twig', [
                'current_route' => 'kantor_cabang',
                'office' => $office,
                'users' => $users,
                'errors' => $errors,
                'old' => $old
            ]);
        } catch (\Exception $e) {
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
            $basePath = '';
            if (strpos($scriptName, '/index.php') !== false) {
                $basePath = str_replace('/public/index.php', '', $scriptName);
            } else {
                $basePath = rtrim(dirname($scriptName), '/');
            }
            $basePath = rtrim($basePath, '/');
            $this->flash->addMessage('error', $e->getMessage());
            return $response->withHeader('Location', $basePath . '/kantor-cabang')->withStatus(302);
        }
    }

    /**
     * Save edited branch office details.
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
            $updateRequest = new UpdateKantorCabangRequest();
            $sanitized = $updateRequest->validate($parsedBody, $id);

            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $this->kantorCabangService->update($id, $sanitized, $operatorId, $ipAddress);

            $this->flash->addMessage('success', 'Data kantor cabang berhasil diperbarui.');
            return $response->withHeader('Location', $basePath . '/kantor-cabang')->withStatus(302);

        } catch (ValidationException $e) {
            $_SESSION['errors'] = $e->getErrors();
            $_SESSION['old'] = $parsedBody;
            $this->flash->addMessage('error', $e->getMessage());
            return $response->withHeader('Location', $basePath . "/kantor-cabang/edit/{$id}")->withStatus(302);
        } catch (\Exception $e) {
            $this->flash->addMessage('error', $e->getMessage());
            return $response->withHeader('Location', $basePath . "/kantor-cabang/edit/{$id}")->withStatus(302);
        }
    }

    /**
     * Delete existing branch office (Soft Delete).
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
            $this->kantorCabangService->delete($id, $operatorId, $ipAddress);

            $this->flash->addMessage('success', 'Kantor cabang berhasil dihapus.');
        } catch (\Exception $e) {
            $this->flash->addMessage('error', $e->getMessage());
        }

        return $response->withHeader('Location', $basePath . '/kantor-cabang')->withStatus(302);
    }
}

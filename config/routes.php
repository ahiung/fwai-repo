<?php

declare(strict_types=1);

use Slim\App;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\ProfileController;
use App\Controllers\RoleController;
use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RbacMiddleware;
use Slim\Flash\Messages;
use Slim\Views\Twig;
use Illuminate\Pagination\Paginator;

/**
 * Register Application Routes (Part 1 - 5 Comprehensive)
 */
return function (App $app) {
    $container = $app->getContainer();
    $flash = $container->get(Messages::class);

    // Root Redirect
    $app->get('/', function ($request, $response) {
        $routeContext = \Slim\Routing\RouteContext::fromRequest($request);
        $basePath = $routeContext->getBasePath();
        return $response->withHeader('Location', $basePath . '/dashboard')->withStatus(302);
    });

    // 1. Authentication Routes (Public)
    $app->get('/login', [AuthController::class, 'loginForm']);
    $app->post('/login', [AuthController::class, 'login']);
    $app->get('/logout', [AuthController::class, 'logout']);

    // 2. Session Protected Dashboard Route
    $app->get('/dashboard', [DashboardController::class, 'index'])
        ->add(new AuthMiddleware($flash));

    // 3. Session Protected Profile Routes
    $app->get('/profile', [ProfileController::class, 'index'])
        ->add(new AuthMiddleware($flash));
    $app->post('/profile', [ProfileController::class, 'update'])
        ->add(new AuthMiddleware($flash));

    // 4. RBAC Granular Protected Modul: Users
    $app->get('/users', [UserController::class, 'index'])
        ->add(new RbacMiddleware($flash, 'view_users'))
        ->add(new AuthMiddleware($flash));
        
    $app->get('/users/add', [UserController::class, 'create'])
        ->add(new RbacMiddleware($flash, 'add_users'))
        ->add(new AuthMiddleware($flash));
        
    $app->post('/users/add', [UserController::class, 'store'])
        ->add(new RbacMiddleware($flash, 'add_users'))
        ->add(new AuthMiddleware($flash));
        
    $app->get('/users/edit/{id}', [UserController::class, 'edit'])
        ->add(new RbacMiddleware($flash, 'edit_users'))
        ->add(new AuthMiddleware($flash));
        
    $app->post('/users/edit/{id}', [UserController::class, 'update'])
        ->add(new RbacMiddleware($flash, 'edit_users'))
        ->add(new AuthMiddleware($flash));
        
    $app->get('/users/delete/{id}', [UserController::class, 'delete'])
        ->add(new RbacMiddleware($flash, 'delete_users'))
        ->add(new AuthMiddleware($flash));

    // 5. RBAC Granular Protected Modul: Roles
    $app->get('/roles', [RoleController::class, 'index'])
        ->add(new RbacMiddleware($flash, 'view_roles'))
        ->add(new AuthMiddleware($flash));
        
    $app->get('/roles/add', [RoleController::class, 'create'])
        ->add(new RbacMiddleware($flash, 'add_roles'))
        ->add(new AuthMiddleware($flash));
        
    $app->post('/roles/add', [RoleController::class, 'store'])
        ->add(new RbacMiddleware($flash, 'add_roles'))
        ->add(new AuthMiddleware($flash));
        
    $app->get('/roles/edit/{id}', [RoleController::class, 'edit'])
        ->add(new RbacMiddleware($flash, 'edit_roles'))
        ->add(new AuthMiddleware($flash));
        
    $app->post('/roles/edit/{id}', [RoleController::class, 'update'])
        ->add(new RbacMiddleware($flash, 'edit_roles'))
        ->add(new AuthMiddleware($flash));
        
    $app->get('/roles/delete/{id}', [RoleController::class, 'delete'])
        ->add(new RbacMiddleware($flash, 'delete_roles'))
        ->add(new AuthMiddleware($flash));

    // 6. RBAC Granular Protected Modul: Audit Trail System Logs (Inline implementation)
    $app->get('/audit-trails', function ($request, $response) use ($container, $flash) {
        $routeContext = \Slim\Routing\RouteContext::fromRequest($request);
        $basePath = $routeContext->getBasePath();

        $queryParams = $request->getQueryParams();
        $page = isset($queryParams['page']) ? (int)$queryParams['page'] : 1;

        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        Paginator::currentPathResolver(function () use ($basePath) {
            return $basePath . '/audit-trails';
        });

        // Paginate logs (15 entries per page) with eager loaded user
        $logsPaginator = \App\Models\AuditTrail::with('user')
            ->orderBy('id', 'desc')
            ->paginate(15);

        $twig = $container->get(Twig::class);
        return $twig->render($response, 'audit_trails/index.twig', [
            'current_route' => 'audit_trails',
            'logs' => $logsPaginator->items(),
            'paginator' => $logsPaginator
        ]);
    })->add(new RbacMiddleware($flash, 'view_audit_trails'))
      ->add(new AuthMiddleware($flash));
};

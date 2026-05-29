<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use App\Models\User;
use App\Models\Role;
use App\Models\AuditTrail;

/**
 * Controller to handle dynamic homepage system dashboard rendering.
 */
class DashboardController
{
    private Twig $view;

    /**
     * DashboardController constructor.
     *
     * @param Twig $view
     */
    public function __construct(Twig $view)
    {
        $this->view = $view;
    }

    /**
     * Renders dashboard dashboard.
     */
    public function index(Request $request, Response $response): Response
    {
        // 1. Gather stats from Eloquent ORM Models
        $totalUsers = User::count();
        $totalRoles = Role::count();
        
        // Count audit logs cleanly
        $totalLogs = AuditTrail::count();

        // 2. Fetch the latest 5 audit trails with User relation
        $recentLogs = AuditTrail::with('user')
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        return $this->view->render($response, 'dashboard/index.twig', [
            'current_route' => 'dashboard',
            'stats' => [
                'users' => $totalUsers,
                'roles' => $totalRoles,
                'logs' => $totalLogs
            ],
            'recent_logs' => $recentLogs
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig Extension to allow easy permission checking in Twig views.
 * Usage: {% if can('view_users') %} ... {% endif %}
 */
class TwigAuthExtension extends AbstractExtension
{
    /**
     * Define custom Twig functions.
     *
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('can', [$this, 'canUser']),
        ];
    }

    /**
     * Check if current session user has the specified permission.
     *
     * @param string $permission
     * @return bool
     */
    public function canUser(string $permission): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $user = $_SESSION['user'] ?? null;
        if (!$user) {
            return false;
        }

        // Superadmin bypass (role_id 1)
        if ((int)$user['role_id'] === 1 || ($user['role_name'] ?? '') === 'Superadmin') {
            return true;
        }

        $permissions = $_SESSION['user_permissions'] ?? [];
        return in_array($permission, $permissions);
    }
}

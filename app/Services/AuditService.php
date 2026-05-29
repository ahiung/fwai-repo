<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditTrail;

/**
 * Service to record and log operations and security events.
 */
class AuditService
{
    /**
     * Record an audit trail entry.
     *
     * @param int|null $userId ID of user performing the action
     * @param string $action Action name (e.g. CREATE, UPDATE, DELETE, LOGIN_FAILED)
     * @param string $module Target module name (e.g. Auth, Users, Roles)
     * @param string $description Detailed audit description
     * @param string $ip Client IP address
     * @return void
     */
    public function log(?int $userId, string $action, string $module, string $description, string $ip): void
    {
        try {
            AuditTrail::create([
                'user_id' => $userId,
                'action' => $action,
                'module' => $module,
                'description' => $description,
                'ip_address' => $ip
            ]);
        } catch (\Exception $e) {
            // Silently fallback if logging fails (e.g. during migrations)
            error_log('Audit Trail Logging Failed: ' . $e->getMessage());
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Exceptions\ValidationException;

/**
 * Service to handle authentication and session authorization setup.
 */
class AuthService
{
    /**
     * @var AuditService
     */
    private AuditService $auditService;

    /**
     * AuthService constructor.
     *
     * @param AuditService $auditService
     */
    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Authenticate user credentials and establish session.
     *
     * @param string $username
     * @param string $password
     * @param string $ipAddress Client IP address for auditing
     * @return void
     * @throws ValidationException
     */
    public function authenticate(string $username, string $password, string $ipAddress): void
    {
        // 1. Fetch user by username including Role relation
        $user = User::where('username', $username)->first();

        // 2. Validate user existence and password verify
        if (!$user || !password_verify($password, $user->password)) {
            // Log security failure audit log
            $this->auditService->log(
                null,
                'LOGIN_FAILED',
                'Auth',
                "Gagal masuk: Upaya login tidak sah untuk username '{$username}'",
                $ipAddress
            );

            throw new ValidationException(
                ['username' => 'Username atau password salah.'],
                'Kredensial login Anda salah.'
            );
        }

        // Ensure session is active
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 3. Load user roles and granular permissions list
        $roleName = 'Guest';
        $permissions = [];

        if ($user->role) {
            $roleName = $user->role->name;
            // Pluck permission names array
            $permissions = $user->role->permissions()->pluck('name')->toArray();
        }

        // 4. Set Session Payload
        $_SESSION['user'] = [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'name' => $user->name,
            'role_id' => $user->role_id,
            'role_name' => $roleName
        ];
        
        $_SESSION['user_permissions'] = $permissions;

        // 5. Log successful authentication audit log
        $this->auditService->log(
            (int)$user->id,
            'LOGIN_SUCCESS',
            'Auth',
            "Berhasil masuk: User '{$user->username}' masuk ke sistem.",
            $ipAddress
        );
    }
}

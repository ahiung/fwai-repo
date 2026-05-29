<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Exceptions\NotFoundException;

/**
 * Service to manage authenticated user profile modifications.
 */
class ProfileService
{
    private AuditService $auditService;

    /**
     * ProfileService constructor.
     *
     * @param AuditService $auditService
     */
    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Update user profile details.
     *
     * @param int $userId ID of user being updated
     * @param array $data Sanitized profile data
     * @param string $ipAddress Client IP address for auditing
     * @return void
     * @throws NotFoundException
     */
    public function update(int $userId, array $data, string $ipAddress): void
    {
        $user = User::find($userId);
        if (!$user) {
            throw new NotFoundException('Data akun tidak ditemukan.');
        }

        $oldName = $user->name;
        $user->name = $data['name'];
        $user->email = $data['email'];

        if (!empty($data['password'])) {
            $user->password = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        $user->save();

        // Sync changes with active session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['user']) && (int)$_SESSION['user']['id'] === $userId) {
            $_SESSION['user']['name'] = $user->name;
            $_SESSION['user']['email'] = $user->email;
        }

        // Log audit log
        $this->auditService->log(
            $userId,
            'UPDATE',
            'Profile',
            "Memperbarui profil: Mengubah nama dari '{$oldName}' menjadi '{$user->name}'",
            $ipAddress
        );
    }
}

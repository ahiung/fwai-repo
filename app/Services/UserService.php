<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Exceptions\NotFoundException;
use App\Exceptions\AuthorizationException;

/**
 * Service to manage users CRUD operations and auditing.
 */
class UserService
{
    private AuditService $auditService;

    /**
     * UserService constructor.
     */
    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Fetch all users with eager-loaded role relations.
     */
    public function getAll(): array
    {
        return User::with('role')->get()->toArray();
    }

    /**
     * Find user by ID or throw NotFoundException.
     *
     * @throws NotFoundException
     */
    public function findById(int $id): User
    {
        $user = User::with('role')->find($id);
        if (!$user) {
            throw new NotFoundException('Data pengguna tidak ditemukan.');
        }
        return $user;
    }

    /**
     * Create and save a new user.
     */
    public function store(array $data, int $operatorId, string $ipAddress): void
    {
        $user = User::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'name' => $data['name'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
            'role_id' => $data['role_id']
        ]);

        // Log audit log
        $this->auditService->log(
            $operatorId,
            'CREATE',
            'Users',
            "Membuat pengguna baru: '{$user->username}' dengan nama '{$user->name}'",
            $ipAddress
        );
    }

    /**
     * Update existing user details.
     *
     * @throws AuthorizationException
     */
    public function update(int $id, array $data, int $operatorId, string $ipAddress): void
    {
        // Prevent editing superadmin account username to bypass security safeguards
        if ($id === 1 && $data['username'] !== 'superadmin') {
            throw new AuthorizationException('Akun Superadmin bawaan tidak dapat diubah usernamenya.');
        }

        $user = $this->findById($id);
        $oldUsername = $user->username;

        $user->username = $data['username'];
        $user->email = $data['email'];
        $user->name = $data['name'];
        $user->role_id = $data['role_id'];

        if (!empty($data['password'])) {
            $user->password = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        $user->save();

        // Log audit log
        $this->auditService->log(
            $operatorId,
            'UPDATE',
            'Users',
            "Mengubah rincian pengguna: Mengubah '{$oldUsername}' menjadi '{$user->username}'",
            $ipAddress
        );
    }

    /**
     * Soft delete user.
     *
     * @throws AuthorizationException
     */
    public function delete(int $id, int $operatorId, string $ipAddress): void
    {
        // Guard Superadmin from deletion
        if ($id === 1) {
            throw new AuthorizationException('Akun Superadmin utama tidak dapat dihapus.');
        }

        // Prevent self deletion
        if ($id === $operatorId) {
            throw new AuthorizationException('Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user = $this->findById($id);
        $username = $user->username;

        // Perform Soft Delete
        $user->delete();

        // Log audit log
        $this->auditService->log(
            $operatorId,
            'DELETE',
            'Users',
            "Menghapus pengguna: Akun '{$username}' dihapus secara lunak (soft delete).",
            $ipAddress
        );
    }
}

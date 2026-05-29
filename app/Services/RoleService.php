<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Role;
use App\Exceptions\NotFoundException;
use App\Exceptions\AuthorizationException;

/**
 * Service to manage roles CRUD and pivot permission synchronization.
 */
class RoleService
{
    /**
     * @var AuditService
     */
    private AuditService $auditService;

    /**
     * RoleService constructor.
     */
    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Fetch all roles with eager-loaded permissions.
     */
    public function getAll(): array
    {
        return Role::with('permissions')->get()->toArray();
    }

    /**
     * Find role by ID or throw NotFoundException.
     *
     * @throws NotFoundException
     */
    public function findById(int $id): Role
    {
        $role = Role::with('permissions')->find($id);
        if (!$role) {
            throw new NotFoundException('Data peran tidak ditemukan.');
        }
        return $role;
    }

    /**
     * Save new role.
     */
    public function store(array $data, int $operatorId, string $ipAddress): void
    {
        $role = Role::create([
            'name' => $data['name'],
            'description' => $data['description']
        ]);

        // Sync permissions relation (M-to-M)
        $role->permissions()->sync($data['permissions']);

        // Log audit log
        $this->auditService->log(
            $operatorId,
            'CREATE',
            'Roles',
            "Membuat peran baru: '{$role->name}' beserta hak akses terasosiasi.",
            $ipAddress
        );
    }

    /**
     * Update existing role.
     *
     * @throws AuthorizationException
     */
    public function update(int $id, array $data, int $operatorId, string $ipAddress): void
    {
        // Prevent editing the core Superadmin role name
        if ($id === 1 && $data['name'] !== 'Superadmin') {
            throw new AuthorizationException('Nama peran Superadmin bawaan tidak dapat diubah.');
        }

        $role = $this->findById($id);
        $oldName = $role->name;

        $role->name = $data['name'];
        $role->description = $data['description'];
        $role->save();

        // Synchronize permissions relations (M-to-M)
        $role->permissions()->sync($data['permissions']);

        // Log audit log
        $this->auditService->log(
            $operatorId,
            'UPDATE',
            'Roles',
            "Mengubah peran: Mengubah rincian peran '{$oldName}' menjadi '{$role->name}' beserta sinkronisasi hak akses.",
            $ipAddress
        );
    }

    /**
     * Remove role via Soft Delete.
     *
     * @throws AuthorizationException
     */
    public function delete(int $id, int $operatorId, string $ipAddress): void
    {
        // Guard core Superadmin role
        if ($id === 1) {
            throw new AuthorizationException('Peran Superadmin bawaan sistem tidak boleh dihapus.');
        }

        $role = $this->findById($id);
        $roleName = $role->name;

        // Perform Soft Delete
        $role->delete();

        // Log audit log
        $this->auditService->log(
            $operatorId,
            'DELETE',
            'Roles',
            "Menghapus peran: Peran '{$roleName}' dihapus secara lunak (soft delete).",
            $ipAddress
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\KantorCabang;
use App\Models\User;
use App\Exceptions\NotFoundException;
use App\Exceptions\AuthorizationException;

/**
 * Service to manage Kantor Cabang (Branch Offices) CRUD operations and auditing.
 */
class KantorCabangService
{
    private AuditService $auditService;

    /**
     * KantorCabangService constructor.
     */
    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Fetch all offices based on operator role and data scope.
     *
     * @param int $operatorId
     * @return array
     */
    public function getAll(int $operatorId): array
    {
        $operator = User::find($operatorId);
        $query = KantorCabang::with(['manager', 'creator', 'updater']);

        // Data Scope: Non-Superadmin can only see offices where they are the manager
        if ($operator && $operator->role_id !== 1) {
            $query->where('manager_id', $operatorId);
        }

        return $query->get()->toArray();
    }

    /**
     * Find office by ID or throw exception, validating data scope ownership.
     *
     * @param int $id
     * @param int $operatorId
     * @return KantorCabang
     * @throws NotFoundException
     * @throws AuthorizationException
     */
    public function findById(int $id, int $operatorId): KantorCabang
    {
        $office = KantorCabang::with(['manager', 'creator', 'updater'])->find($id);
        if (!$office) {
            throw new NotFoundException('Data kantor cabang tidak ditemukan.');
        }

        // Data Scope Validation: Non-Superadmin can only access their own managed office
        $operator = User::find($operatorId);
        if ($operator && $operator->role_id !== 1 && $office->manager_id !== $operatorId) {
            throw new AuthorizationException('Anda tidak memiliki wewenang untuk mengakses data kantor cabang ini.');
        }

        return $office;
    }

    /**
     * Create and save a new office branch.
     *
     * @param array $data Sanitized post data
     * @param int $operatorId
     * @param string $ipAddress
     */
    public function store(array $data, int $operatorId, string $ipAddress): void
    {
        $office = KantorCabang::create([
            'office_code' => $data['office_code'],
            'name' => $data['name'],
            'office_type' => $data['office_type'],
            'address' => $data['address'],
            'city' => $data['city'],
            'province' => $data['province'],
            'postal_code' => $data['postal_code'],
            'country' => $data['country'] ?? 'Indonesia',
            'phone' => $data['phone'],
            'email' => $data['email'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'manager_id' => $data['manager_id'],
            'status' => $data['status'] ?? 'active',
            'created_by' => $operatorId,
            'updated_by' => $operatorId
        ]);

        // Log audit trail
        $this->auditService->log(
            $operatorId,
            'CREATE',
            'KantorCabang',
            "Membuat kantor cabang baru: '{$office->name}' dengan kode '{$office->office_code}'",
            $ipAddress
        );
    }

    /**
     * Update existing office details.
     *
     * @param int $id
     * @param array $data Sanitized post data
     * @param int $operatorId
     * @param string $ipAddress
     * @throws NotFoundException
     * @throws AuthorizationException
     */
    public function update(int $id, array $data, int $operatorId, string $ipAddress): void
    {
        // Find and validate ownership
        $office = $this->findById($id, $operatorId);
        
        $oldName = $office->name;
        $oldCode = $office->office_code;

        $office->office_code = $data['office_code'];
        $office->name = $data['name'];
        $office->office_type = $data['office_type'];
        $office->address = $data['address'];
        $office->city = $data['city'];
        $office->province = $data['province'];
        $office->postal_code = $data['postal_code'];
        $office->country = $data['country'] ?? 'Indonesia';
        $office->phone = $data['phone'];
        $office->email = $data['email'];
        $office->latitude = $data['latitude'];
        $office->longitude = $data['longitude'];
        $office->manager_id = $data['manager_id'];
        $office->status = $data['status'] ?? 'active';
        $office->updated_by = $operatorId;

        $office->save();

        // Log audit trail
        $this->auditService->log(
            $operatorId,
            'UPDATE',
            'KantorCabang',
            "Mengubah rincian kantor cabang: Mengubah '{$oldName}' ({$oldCode}) menjadi '{$office->name}' ({$office->office_code})",
            $ipAddress
        );
    }

    /**
     * Soft delete office branch.
     *
     * @param int $id
     * @param int $operatorId
     * @param string $ipAddress
     * @throws NotFoundException
     * @throws AuthorizationException
     */
    public function delete(int $id, int $operatorId, string $ipAddress): void
    {
        // Find and validate ownership
        $office = $this->findById($id, $operatorId);
        
        $name = $office->name;
        $code = $office->office_code;

        // Perform Soft Delete
        $office->delete();

        // Log audit trail
        $this->auditService->log(
            $operatorId,
            'DELETE',
            'KantorCabang',
            "Menghapus kantor cabang: Kantor '{$name}' ({$code}) dihapus secara lunak (soft delete).",
            $ipAddress
        );
    }
}

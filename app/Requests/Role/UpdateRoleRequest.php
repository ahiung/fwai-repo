<?php

declare(strict_types=1);

namespace App\Requests\Role;

use App\Exceptions\ValidationException;
use App\Models\Role;

/**
 * Validates post parameters for Role modification operation.
 */
class UpdateRoleRequest
{
    /**
     * Validate and sanitize raw roles data.
     *
     * @param array $data Raw POST input parameters
     * @param int $roleId Target role ID
     * @return array Sanitized inputs if valid
     * @throws ValidationException
     */
    public function validate(array $data, int $roleId): array
    {
        $errors = [];
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        $permissions = $data['permissions'] ?? [];

        if (empty($name)) {
            $errors['name'] = 'Nama peran wajib diisi.';
        } else {
            // Unique role name validation except self
            $roleExists = Role::where('name', $name)->where('id', '!=', $roleId)->exists();
            if ($roleExists) {
                $errors['name'] = 'Nama peran sudah digunakan.';
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors, 'Silakan periksa kembali data input peran.');
        }

        return [
            'name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
            'description' => htmlspecialchars($description, ENT_QUOTES, 'UTF-8'),
            'permissions' => is_array($permissions) ? array_map('intval', $permissions) : []
        ];
    }
}

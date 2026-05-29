<?php

declare(strict_types=1);

namespace App\Requests\Role;

use App\Exceptions\ValidationException;
use App\Models\Role;

/**
 * Validates post parameters for Role creation operation.
 */
class StoreRoleRequest
{
    /**
     * Validate and sanitize raw roles data.
     *
     * @param array $data Raw POST input parameters
     * @return array Sanitized inputs if valid
     * @throws ValidationException
     */
    public function validate(array $data): array
    {
        $errors = [];
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        $permissions = $data['permissions'] ?? []; // Array of permission IDs

        if (empty($name)) {
            $errors['name'] = 'Nama peran wajib diisi.';
        } else {
            // Unique role name validation
            $roleExists = Role::where('name', $name)->exists();
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

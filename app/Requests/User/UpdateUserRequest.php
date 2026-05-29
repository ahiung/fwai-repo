<?php

declare(strict_types=1);

namespace App\Requests\User;

use App\Exceptions\ValidationException;
use App\Models\User;

/**
 * Validates post parameters for User modification operation.
 */
class UpdateUserRequest
{
    /**
     * Validate and sanitize raw User data.
     *
     * @param array $data Raw POST input parameters
     * @param int $userId Target user ID
     * @return array Sanitized inputs if valid
     * @throws ValidationException
     */
    public function validate(array $data, int $userId): array
    {
        $errors = [];
        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $name = trim($data['name'] ?? '');
        $password = $data['password'] ?? '';
        $roleId = isset($data['role_id']) ? (int)$data['role_id'] : null;

        if (empty($username)) {
            $errors['username'] = 'Username wajib diisi.';
        } elseif (strlen($username) < 3) {
            $errors['username'] = 'Username minimal terdiri dari 3 karakter.';
        } elseif (User::where('username', $username)->where('id', '!=', $userId)->exists()) {
            $errors['username'] = 'Username sudah digunakan oleh pengguna lain.';
        }

        if (empty($email)) {
            $errors['email'] = 'Alamat email wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format alamat email tidak valid.';
        } elseif (User::where('email', $email)->where('id', '!=', $userId)->exists()) {
            $errors['email'] = 'Alamat email sudah terdaftar untuk pengguna lain.';
        }

        if (empty($name)) {
            $errors['name'] = 'Nama lengkap wajib diisi.';
        }

        if (!empty($password) && strlen($password) < 6) {
            $errors['password'] = 'Password minimal terdiri dari 6 karakter.';
        }

        if (!$roleId) {
            $errors['role_id'] = 'Pilih salah satu peran (role).';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors, 'Silakan periksa kembali data input pengguna.');
        }

        $sanitized = [
            'username' => htmlspecialchars($username, ENT_QUOTES, 'UTF-8'),
            'email' => htmlspecialchars($email, ENT_QUOTES, 'UTF-8'),
            'name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
            'role_id' => $roleId
        ];

        if (!empty($password)) {
            $sanitized['password'] = $password;
        }

        return $sanitized;
    }
}

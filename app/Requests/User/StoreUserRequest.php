<?php

declare(strict_types=1);

namespace App\Requests\User;

use App\Exceptions\ValidationException;
use App\Models\User;

/**
 * Validates post parameters for User creation operation.
 */
class StoreUserRequest
{
    /**
     * Validate and sanitize raw User data.
     *
     * @param array $data Raw POST input parameters
     * @return array Sanitized inputs if valid
     * @throws ValidationException
     */
    public function validate(array $data): array
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
        } elseif (User::where('username', $username)->exists()) {
            $errors['username'] = 'Username sudah digunakan.';
        }

        if (empty($email)) {
            $errors['email'] = 'Alamat email wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format alamat email tidak valid.';
        } elseif (User::where('email', $email)->exists()) {
            $errors['email'] = 'Alamat email sudah terdaftar.';
        }

        if (empty($name)) {
            $errors['name'] = 'Nama lengkap wajib diisi.';
        }

        if (empty($password)) {
            $errors['password'] = 'Password wajib diisi.';
        } elseif (strlen($password) < 6) {
            $errors['password'] = 'Password minimal terdiri dari 6 karakter.';
        }

        if (!$roleId) {
            $errors['role_id'] = 'Pilih salah satu peran (role).';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors, 'Silakan periksa kembali data input pengguna baru.');
        }

        return [
            'username' => htmlspecialchars($username, ENT_QUOTES, 'UTF-8'),
            'email' => htmlspecialchars($email, ENT_QUOTES, 'UTF-8'),
            'name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
            'password' => $password,
            'role_id' => $roleId
        ];
    }
}

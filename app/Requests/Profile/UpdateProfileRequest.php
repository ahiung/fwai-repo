<?php

declare(strict_types=1);

namespace App\Requests\Profile;

use App\Exceptions\ValidationException;
use App\Models\User;

/**
 * Validates post parameters for Profile update operation.
 */
class UpdateProfileRequest
{
    /**
     * Validate and sanitize raw profile data.
     *
     * @param array $data Raw POST input parameters
     * @param int $userId Current user ID (to bypass unique email check on self)
     * @return array Sanitized inputs if valid
     * @throws ValidationException
     */
    public function validate(array $data, int $userId): array
    {
        $errors = [];
        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $passwordConfirm = $data['password_confirm'] ?? '';

        if (empty($name)) {
            $errors['name'] = 'Nama lengkap wajib diisi.';
        }

        if (empty($email)) {
            $errors['email'] = 'Alamat email wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format alamat email tidak valid.';
        } else {
            // Check if email already used by another user
            $emailExists = User::where('email', $email)->where('id', '!=', $userId)->exists();
            if ($emailExists) {
                $errors['email'] = 'Alamat email sudah digunakan oleh pengguna lain.';
            }
        }

        // If password is being updated
        if (!empty($password)) {
            if (strlen($password) < 6) {
                $errors['password'] = 'Password minimal terdiri dari 6 karakter.';
            }
            if ($password !== $passwordConfirm) {
                $errors['password_confirm'] = 'Konfirmasi password tidak cocok.';
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors, 'Silakan periksa kembali rincian input profil Anda.');
        }

        $sanitized = [
            'name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
            'email' => htmlspecialchars($email, ENT_QUOTES, 'UTF-8')
        ];

        if (!empty($password)) {
            $sanitized['password'] = $password;
        }

        return $sanitized;
    }
}

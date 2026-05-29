<?php

declare(strict_types=1);

namespace App\Requests\Auth;

use App\Exceptions\ValidationException;

/**
 * Validates post parameters for Login operation.
 */
class LoginRequest
{
    /**
     * Validate and sanitize raw request POST parameters.
     *
     * @param array $data Raw POST inputs from request
     * @return array Sanitized inputs if valid
     * @throws ValidationException
     */
    public function validate(array $data): array
    {
        $errors = [];
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';

        if (empty($username)) {
            $errors['username'] = 'Username wajib diisi.';
        }
        
        if (empty($password)) {
            $errors['password'] = 'Password wajib diisi.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors, 'Kredensial login tidak lengkap.');
        }

        // Return clean sanitized values
        return [
            'username' => htmlspecialchars($username, ENT_QUOTES, 'UTF-8'),
            'password' => $password
        ];
    }
}

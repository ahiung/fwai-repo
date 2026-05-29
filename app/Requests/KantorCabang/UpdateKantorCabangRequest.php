<?php

declare(strict_types=1);

namespace App\Requests\KantorCabang;

use App\Exceptions\ValidationException;
use App\Models\KantorCabang;
use App\Models\User;

/**
 * Validates post parameters for Kantor Cabang update operation.
 */
class UpdateKantorCabangRequest
{
    /**
     * Validate and sanitize raw Kantor Cabang data.
     *
     * @param array $data Raw POST input parameters
     * @param int $id The ID of the record being updated
     * @return array Sanitized inputs if valid
     * @throws ValidationException
     */
    public function validate(array $data, int $id): array
    {
        $errors = [];

        $officeCode = trim($data['office_code'] ?? '');
        $name = trim($data['name'] ?? '');
        $officeType = trim($data['office_type'] ?? 'branch');
        $address = isset($data['address']) ? trim($data['address']) : null;
        $city = isset($data['city']) ? trim($data['city']) : null;
        $province = isset($data['province']) ? trim($data['province']) : null;
        $postalCode = isset($data['postal_code']) ? trim($data['postal_code']) : null;
        $country = isset($data['country']) ? trim($data['country']) : 'Indonesia';
        $phone = isset($data['phone']) ? trim($data['phone']) : null;
        $email = isset($data['email']) ? trim($data['email']) : null;
        $latitude = isset($data['latitude']) && $data['latitude'] !== '' ? (float)$data['latitude'] : null;
        $longitude = isset($data['longitude']) && $data['longitude'] !== '' ? (float)$data['longitude'] : null;
        $managerId = isset($data['manager_id']) && $data['manager_id'] !== '' ? (int)$data['manager_id'] : null;
        $status = trim($data['status'] ?? 'active');

        // office_code validation
        if (empty($officeCode)) {
            $errors['office_code'] = 'Kode kantor wajib diisi.';
        } elseif (strlen($officeCode) < 3) {
            $errors['office_code'] = 'Kode kantor minimal terdiri dari 3 karakter.';
        } elseif (strlen($officeCode) > 50) {
            $errors['office_code'] = 'Kode kantor maksimal 50 karakter.';
        } elseif (KantorCabang::where('office_code', $officeCode)->where('id', '!=', $id)->exists()) {
            $errors['office_code'] = 'Kode kantor sudah digunakan.';
        }

        // name validation
        if (empty($name)) {
            $errors['name'] = 'Nama kantor wajib diisi.';
        } elseif (strlen($name) < 3) {
            $errors['name'] = 'Nama kantor minimal terdiri dari 3 karakter.';
        } elseif (strlen($name) > 150) {
            $errors['name'] = 'Nama kantor maksimal 150 karakter.';
        }

        // office_type validation
        if (!in_array($officeType, ['branch', 'head', 'remote'], true)) {
            $errors['office_type'] = 'Tipe kantor tidak valid (harus branch, head, atau remote).';
        }

        // status validation
        if (!in_array($status, ['active', 'inactive'], true)) {
            $errors['status'] = 'Status tidak valid (harus active atau inactive).';
        }

        // email validation
        if (!empty($email)) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Format email tidak valid.';
            } elseif (strlen($email) > 100) {
                $errors['email'] = 'Alamat email maksimal 100 karakter.';
            }
        }

        // latitude & longitude validation
        if ($latitude !== null) {
            if ($latitude < -90.0 || $latitude > 90.0) {
                $errors['latitude'] = 'Latitude harus bernilai antara -90 hingga 90.';
            }
        }
        if ($longitude !== null) {
            if ($longitude < -180.0 || $longitude > 180.0) {
                $errors['longitude'] = 'Longitude harus bernilai antara -180 hingga 180.';
            }
        }

        // manager_id validation
        if ($managerId !== null) {
            if ($managerId <= 0 || !User::where('id', $managerId)->exists()) {
                $errors['manager_id'] = 'Manager yang dipilih tidak valid.';
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors, 'Silakan periksa kembali data input kantor cabang.');
        }

        return [
            'office_code' => htmlspecialchars($officeCode, ENT_QUOTES, 'UTF-8'),
            'name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
            'office_type' => $officeType,
            'address' => $address ? htmlspecialchars($address, ENT_QUOTES, 'UTF-8') : null,
            'city' => $city ? htmlspecialchars($city, ENT_QUOTES, 'UTF-8') : null,
            'province' => $province ? htmlspecialchars($province, ENT_QUOTES, 'UTF-8') : null,
            'postal_code' => $postalCode ? htmlspecialchars($postalCode, ENT_QUOTES, 'UTF-8') : null,
            'country' => htmlspecialchars($country, ENT_QUOTES, 'UTF-8'),
            'phone' => $phone ? htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') : null,
            'email' => $email ? htmlspecialchars($email, ENT_QUOTES, 'UTF-8') : null,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'manager_id' => $managerId,
            'status' => $status
        ];
    }
}

<?php

declare(strict_types=1);

if (!function_exists('format_rupiah')) {
    /**
     * Format number to Indonesian Rupiah
     *
     * @param float|int $angka
     * @param bool $denganSen
     * @return string
     */
    function format_rupiah($angka, bool $denganSen = false): string
    {
        $format = number_format((float)$angka, $denganSen ? 2 : 0, ',', '.');
        return 'Rp ' . $format;
    }
}

if (!function_exists('format_tanggal')) {
    /**
     * Format date to Indonesian style or custom format
     *
     * @param string|null $date
     * @param string $format
     * @return string
     */
    function format_tanggal(?string $date, string $format = 'd-m-Y H:i'): string
    {
        if (empty($date)) {
            return '-';
        }
        try {
            $dateTime = new DateTime($date, new DateTimeZone('Asia/Jakarta'));
            return $dateTime->format($format);
        } catch (Exception $e) {
            return '-';
        }
    }
}

if (!function_exists('generate_kode')) {
    /**
     * Generate unique incremental code with a prefix
     *
     * @param string $prefix
     * @param string $table
     * @param string $column
     * @param int $length
     * @return string
     */
    function generate_kode(string $prefix, string $table, string $column, int $length = 5): string
    {
        try {
            $lastRecord = \Illuminate\Database\Capsule\Manager::table($table)
                ->orderBy('id', 'desc')
                ->first();

            if ($lastRecord && isset($lastRecord->{$column})) {
                $lastCode = (string)$lastRecord->{$column};
                $lastNumString = substr($lastCode, strlen($prefix));
                $lastNum = is_numeric($lastNumString) ? (int)$lastNumString : 0;
                $nextNum = $lastNum + 1;
            } else {
                $nextNum = 1;
            }
        } catch (Exception $e) {
            $nextNum = 1;
        }

        return $prefix . str_pad((string)$nextNum, $length, '0', STR_PAD_LEFT);
    }
}

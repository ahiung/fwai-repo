<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * NotFoundException thrown when record or resource is missing.
 */
class NotFoundException extends AppException
{
    /**
     * NotFoundException constructor.
     *
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct(string $message = "Data tidak ditemukan.", int $code = 404, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

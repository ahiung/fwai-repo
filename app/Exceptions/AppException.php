<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Base AppException for the application.
 * All custom domain exceptions should extend this class.
 */
class AppException extends Exception
{
    /**
     * AppException constructor.
     *
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct(string $message = "Terjadi kesalahan internal sistem.", int $code = 500, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

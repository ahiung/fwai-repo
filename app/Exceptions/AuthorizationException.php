<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * AuthorizationException thrown when user does not have permission or data scope ownership.
 */
class AuthorizationException extends AppException
{
    /**
     * AuthorizationException constructor.
     *
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct(string $message = "Akses ditolak.", int $code = 403, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

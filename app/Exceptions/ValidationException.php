<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * ValidationException thrown when request input validation fails.
 */
class ValidationException extends AppException
{
    /**
     * @var array
     */
    private array $errors;

    /**
     * ValidationException constructor.
     *
     * @param array $errors Validation error messages grouped by field
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct(array $errors, string $message = "Data yang dikirimkan tidak valid.", int $code = 422, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    /**
     * Get validation errors
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}

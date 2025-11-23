<?php

namespace App\Exceptions;

use RuntimeException;

class HttpException extends RuntimeException
{
    private int $statusCode;

    /** @var array<int|string, mixed>|null */
    private ?array $errors;

    /**
     * @param array<int|string, mixed>|null $errors
     */
    public function __construct(string $message, int $statusCode = 400, ?array $errors = null)
    {
        parent::__construct($message, $statusCode);
        $this->statusCode = $statusCode;
        $this->errors = $errors;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<int|string, mixed>|null
     */
    public function getErrors(): ?array
    {
        return $this->errors;
    }
}

<?php

declare(strict_types=1);

namespace OneClickDz\OCPay\Exception;

/**
 * Exception thrown when API returns an error response
 */
class ApiException extends OCPayException
{
    private ?string $requestId = null;
    private ?int $statusCode = null;
    private ?array $errorData = null;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        ?string $requestId = null,
        ?int $statusCode = null,
        ?array $errorData = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->requestId = $requestId;
        $this->statusCode = $statusCode;
        $this->errorData = $errorData;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function getErrorData(): ?array
    {
        return $this->errorData;
    }
}


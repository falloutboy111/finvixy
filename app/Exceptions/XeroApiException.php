<?php

namespace App\Exceptions;

use RuntimeException;

class XeroApiException extends RuntimeException
{
    public function __construct(string $message, private readonly array $responseBody = [])
    {
        parent::__construct($message);
    }

    public function getResponseBody(): array
    {
        return $this->responseBody;
    }
}

<?php

namespace App\Exceptions;

use RuntimeException;

class XeroReauthRequired extends RuntimeException
{
    public function __construct(string $message = 'Xero connection has expired. Please reconnect your Xero account.')
    {
        parent::__construct($message);
    }
}

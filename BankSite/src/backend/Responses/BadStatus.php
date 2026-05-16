<?php

namespace App\Responses;

use Exception;

class BadStatus extends LoggedResponse
{
    public function __construct(int $statusCode)
    {
        $exception = new Exception("Status code not found");
        $this->log($exception, "APP", "There is no response class responsible for {$statusCode} status code", "**Bad status code**");
    }
}
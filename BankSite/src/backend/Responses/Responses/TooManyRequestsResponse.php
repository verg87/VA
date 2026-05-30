<?php

declare(strict_types=1);

namespace App\Responses\Responses;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class TooManyRequestsResponse extends Response
{
    public function __invoke(
        array $headers = [], 
        string $status = "error",
        string $message = "Too many requests"
    ): ResponseInterface
    {
        return new Response(
            429, $headers, json_encode(["status" => $status, "message" => $message])
        );
    }
}
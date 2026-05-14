<?php

declare(strict_types=1);

namespace App\Responses;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class UnauthorizedResponse extends Response
{
    public function __invoke(
        array $headers = [], 
        string $status = "error",
         string $message = "Unauthorized access"
    ): ResponseInterface
    {
        return new Response(
            401, $headers, json_encode(["status" => $status, "message" => $message])
        );
    }
}
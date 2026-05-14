<?php

declare(strict_types=1);

namespace App\Responses;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class InternalServerErrorResponse extends Response
{
    public function __invoke(
        array $headers = [], 
        string $status = "error",
        string $message = "Internal server error"
    ): ResponseInterface
    {
        return new Response(
            500, $headers, json_encode(["status" => $status, "message" => $message])
        );
    }
}
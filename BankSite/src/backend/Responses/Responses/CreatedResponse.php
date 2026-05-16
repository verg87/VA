<?php

declare(strict_types=1);

namespace App\Responses\Responses;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class CreatedResponse extends Response
{
    public function __invoke(
        array $headers = [], 
        string $status = "success",
        string $message = "Resource created"
    ): ResponseInterface
    {
        return new Response(
            201, $headers, json_encode(["status" => $status, "message" => $message])
        );
    }
}
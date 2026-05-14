<?php

declare(strict_types=1);

namespace App\Responses;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class OKResponse extends Response
{
    public function __invoke(
        array $headers = [], 
        string $status = "success",
        string $message = "Request handled sucessfully",
        array $data = [],
    ): ResponseInterface
    {
        return new Response(
            200, $headers, json_encode(["status" => $status, "message" => $message, "data" => $data])
        );
    }
}
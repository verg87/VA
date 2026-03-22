<?php

declare(strict_types=1);

namespace App\Responses;

require_once __DIR__ . "\\..\\..\\..\\vendor\\autoload.php";

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class NotFoundResponse 
{
    public function __invoke(
        array $headers = [], 
        string $status = "error",
        string $message = "Resource not found"
    ): ResponseInterface
    {
        return new Response(
            404, $headers, json_encode(["status" => $status, "message" => $message])
        );
    }
}
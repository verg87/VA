<?php

declare(strict_types=1);

namespace App\Responses;

require_once __DIR__ . "\\..\\..\\..\\vendor\\autoload.php";

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class BadRequestResponse 
{
    public function __invoke(
        array $headers = [], 
        string $status = "error",
        string $message = "Bad request"
    ): ResponseInterface
    {
        return new Response(
            400, $headers, json_encode(["status" => $status, "message" => $message])
        );
    }
}
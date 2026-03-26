<?php

declare(strict_types=1);

namespace App\Responses;

require_once __DIR__ . "\\..\\..\\..\\vendor\\autoload.php";

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class CreatedResponse 
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
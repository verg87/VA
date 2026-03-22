<?php

declare(strict_types=1);

namespace App\Responses;

require_once __DIR__ . "\\..\\..\\..\\vendor\\autoload.php";

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class MethodNotAllowedResponse 
{
    public function __invoke(
        array $headers = [], 
        string $status = "error",
        string $message = "Method not allowed"
    ): ResponseInterface
    {
        return new Response(
            405, $headers, json_encode(["status" => $status, "message" => $message])
        );
    }
}
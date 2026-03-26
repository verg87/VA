<?php

declare(strict_types=1);

namespace App\Responses;

require_once __DIR__ . "\\..\\..\\..\\vendor\\autoload.php";

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class FoundResponse 
{
    public function __invoke(
        array $headers = [], 
        string $status = "success",
        string $message = "Redirect to other page"
    ): ResponseInterface
    {
        return new Response(
            302, $headers, json_encode(["status" => $status, "message" => $message])
        );
    }
}
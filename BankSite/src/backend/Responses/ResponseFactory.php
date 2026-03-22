<?php

declare(strict_types=1);

namespace App\Responses;

require_once __DIR__ . "\\..\\..\\..\\vendor\\autoload.php";

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class ResponseFactory
{
    public static function create(int $statusCode)
    {
        return match($statusCode) 
        {
            200 => new OKResponse(),
            400 => new BadRequestResponse(),
            401 => new UnauthorizedResponse(),
            404 => new NotFoundResponse(),
            405 => new MethodNotAllowedResponse(),
            500 => new InternalServerErrorResponse(),
        };
    }
}
<?php

declare(strict_types=1);

namespace App\Responses;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class ResponseFactory
{
    public static function create(int $statusCode): ResponseInterface
    {
        return match($statusCode) 
        {
            200 => new OKResponse(),
            201 => new CreatedResponse(),
            302 => new FoundResponse(),
            400 => new BadRequestResponse(),
            401 => new UnauthorizedResponse(),
            404 => new NotFoundResponse(),
            405 => new MethodNotAllowedResponse(),
            500 => new InternalServerErrorResponse(),
        };
    }
}
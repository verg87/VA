<?php

declare(strict_types=1);

namespace App\Responses;

use Psr\Http\Message\ResponseInterface;

class ResponseFactory
{
    public static function create(int $statusCode): ResponseInterface
    {
        return match($statusCode) 
        {
            200 => new Responses\OKResponse(),
            201 => new Responses\CreatedResponse(),
            302 => new Responses\FoundResponse(),
            400 => new Responses\BadRequestResponse(),
            401 => new Responses\UnauthorizedResponse(),
            404 => new Responses\NotFoundResponse(),
            405 => new Responses\MethodNotAllowedResponse(),
            500 => new Responses\InternalServerErrorResponse(),
            default => new BadStatus($statusCode),
        };
    }
}
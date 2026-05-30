<?php

namespace App\Responses;

use Psr\Http\Message\ServerRequestInterface;
use Throwable;

use App\Traits\LoggerTrait;

class LoggedResponse extends Responses\InternalServerErrorResponse
{
    use LoggerTrait;

    public function __construct(private Throwable $exception, private ServerRequestInterface $request)
    {
        $this->log($exception, "CONTROLLER", $request->getMethod(), (string) $request->getUri());
    }
}
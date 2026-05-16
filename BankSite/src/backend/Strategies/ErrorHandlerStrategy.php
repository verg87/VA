<?php

namespace App\Strategies;

use League\Route\Strategy\ApplicationStrategy;
use Psr\Http\Server\MiddlewareInterface;
use Throwable;

use App\Middleware\ErrorHandlerMiddleware;

class ErrorHandlerStrategy extends ApplicationStrategy
{
    protected function throwThrowableMiddleware(Throwable $error): MiddlewareInterface
    {
        return new ErrorHandlerMiddleware($error, "ROUTE");
    }
}
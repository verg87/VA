<?php

namespace App\Strategies;

use App\Middleware\ErrorHandlerMiddleware;
use League\Route\Http\Exception\{MethodNotAllowedException, NotFoundException};
use League\Route\Strategy\ApplicationStrategy;
use Psr\Http\Server\MiddlewareInterface;
use Throwable;

class ErrorHandlerStrategy extends ApplicationStrategy
{
    protected Throwable $exception;

    public function getMethodNotAllowedDecorator(MethodNotAllowedException $exception): MiddlewareInterface
    {
        $this->exception = $exception;
        return $this->getThrowableHandler();
    }

    public function getNotFoundDecorator(NotFoundException $exception): MiddlewareInterface
    {
        $this->exception = $exception;
        return $this->getThrowableHandler();
    }

    public function getThrowableHandler(): MiddlewareInterface
    {
        var_dump($this->exception);
        return new ErrorHandlerMiddleware($this->exception, "app_errors", "app_err");
    }
}
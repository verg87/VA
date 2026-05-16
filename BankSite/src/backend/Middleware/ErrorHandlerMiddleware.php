<?php

namespace App\Middleware;

use League\Route\Http\Exception;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

use App\Responses\ResponseFactory;
use App\Helpers\LoggerTrait;

class ErrorHandlerMiddleware implements MiddlewareInterface
{
    use LoggerTrait;
    protected Throwable|Exception|null $exception;
    protected string $name;

    public function __construct(Throwable|Exception|null $exception, string $name) 
    {
        $this->exception = $exception;
        $this->name = $name;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        if ($this->exception === null) {
            return $handler->handle($request);
        } else {
            $this->log($this->exception, $this->name, $request->getMethod(), (string) $request->getUri());

            if ($this->exception instanceof Exception) {
                return ResponseFactory::create($this->exception->getStatusCode())();
            }

            return ResponseFactory::create(500)();
        }
    }
}
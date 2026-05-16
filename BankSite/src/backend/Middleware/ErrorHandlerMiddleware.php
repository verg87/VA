<?php

namespace App\Middleware;

use App\Responses\ResponseFactory;
use Monolog\Logger;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use App\Log;

class ErrorHandlerMiddleware implements MiddlewareInterface
{
    protected Log $log;
    protected Throwable|null $exception;

    public function __construct(Throwable|null $exception, string $name, string $file) 
    {
        $this->log = new Log($name, $file, Logger::ERROR);
        $this->exception = $exception;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        if ($this->exception === null) {
            return $handler->handle($request);
        } else {
            $this->log($this->exception);
            return ResponseFactory::create(500)();
        }
    }

    private function log(Throwable $exception): void
    {
        $message = $exception->getMessage();
        $file = $exception->getFile();
        $line = $exception->getLine();

        $logMessage = "{$message}, {$file}, {$line}" . "\n";

        $this->log->error($logMessage);
    }
}
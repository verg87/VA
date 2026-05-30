<?php

namespace App\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

use App\Responses\ResponseFactory;
use App\Services\RateLimiterService;
use App\Traits\IpRetrieverTrait;

class RateLimiterMiddleware implements MiddlewareInterface
{
    use IpRetrieverTrait;

    public function __construct(private RateLimiterService $limiterService) 
    {  
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $identifier = $this->getIpAddress($request);
        $limiter = $this->limiterService->get("bank_api")->create($identifier);

        $result = $limiter->consume(1);

        if (!$result->isAccepted()) {
            return ResponseFactory::create(429)();
        }

        return $handler->handle($request);
    }
}
<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use App\Models\User;

use App\Vault\Vault;
use App\Responses\ResponseFactory;

class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private Vault $vault, private User $user)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $cookie = $request->getCookieParams()["access-token"] ?? false;

        if ($cookie == false) {
            var_dump($cookie);
            var_dump("no cookie");
            return ResponseFactory::create(401)();
        }

        try {
            $payload = (array) JWT::decode($cookie, new Key($this->vault->getKV("jwtkey"), $_ENV["ALGORITHM"]));
        } catch (\Throwable $e) {
            var_dump($e->getMessage());
            return ResponseFactory::create(401)();
        }

        $user = $this->user->getById($payload["sub"]);

        if (!$user) {
            var_dump($user);
            var_dump("its me");
            return ResponseFactory::create(404)();
        }

        return $handler->handle($request->withAttribute("user", $user));
    }
}
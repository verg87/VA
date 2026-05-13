<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Vault\Vault;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use App\Controller;
use App\Models\User;
use App\Responses\ResponseFactory;

class AuthController extends Controller
{
    public function __construct(private User $user, private Vault $vault)
    {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return $this->post($request);
    }

    private function post(ServerRequestInterface $request): ResponseInterface
    {
        $cookie = $request->getCookieParams()["access-token"] ?? false;

        if ($cookie == false) {
            return ResponseFactory::create(401)();
        } 

        try {
            JWT::decode($cookie, new Key($this->vault->getKV("jwtkey"), $_ENV["ALGORITHM"]));
        } catch (\Throwable $e) {
            return ResponseFactory::create(401)();
        }

        return ResponseFactory::create(200)(message: "Access granted");
    }
}
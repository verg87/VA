<?php

declare(strict_types=1);

namespace App\Controllers;

require_once __DIR__ . "\\..\\..\\..\\vendor\\autoload.php";

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use App\Controller;
use App\Models\User;
use App\Responses\ResponseFactory;

class AuthController extends Controller
{
    public function __construct(private User $user)
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
            JWT::decode($cookie, new Key($_ENV["SECRET_KEY"], $_ENV["ALGORITHM"]));
        } catch (\Throwable $e) {
            return ResponseFactory::create(401)();
        }

        return ResponseFactory::create(200)(message: "Access granted");
    }
}
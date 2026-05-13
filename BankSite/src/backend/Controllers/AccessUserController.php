<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use App\Controller;
use App\Models\User;
use App\Vault\Vault;
use App\Responses\ResponseFactory;

class AccessUserController extends Controller
{
    public function __construct(private User $user, private Vault $vault)
    {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        switch ($request->getMethod()) {
            case "GET":
                return $this->get($request);
            case "POST":
                return $this->post($request);
        }

        return ResponseFactory::create(405)();
    }

    private function get(ServerRequestInterface $request): ResponseInterface
    {
        list("query" => $query) = $this->requestInfo($request);

        if (isset($query["phone_number"]) && $query["phone_number"] !== "") {
            $user = $this->user->getByPhone($query["phone_number"]);

            if (gettype($user) === "array") {
                return ResponseFactory::create(200)(data: $user);
            }

            return ResponseFactory::create(404)();
        }

        return ResponseFactory::create(400)();
    }

    private function post(ServerRequestInterface $request): ResponseInterface
    {
        $cookie = $request->getCookieParams()["access-token"] ?? false;

        if ($cookie == false) {
            return ResponseFactory::create(401)();
        } 

        try {
            $payload = (array) JWT::decode($cookie, new Key($this->vault->getKV("jwtkey"), $_ENV["ALGORITHM"]));
        } catch (\Throwable $e) {
            return ResponseFactory::create(401)();
        }

        $user = $this->user->getById($payload["sub"]);
        
        if (!$user) {
            return ResponseFactory::create(500)();
        }

        // unset($user["id"]);
        unset($user["password"]);

        return ResponseFactory::create(200)(data: $user);
    }
}
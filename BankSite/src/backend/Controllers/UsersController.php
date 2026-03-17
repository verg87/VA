<?php

declare(strict_types=1);

namespace App\Controllers;

require_once __DIR__ . "\\..\\..\\..\\vendor\\autoload.php";

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use App\Models\Users;
use App\Helpers\JWTHelper;
use App\Helpers\Functions;

class UsersController 
{
    public function __construct(private Users $users) 
    {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() === "GET") {
            return $this->get($request);
        } else if ($request->getMethod() === "POST") {
            return $this->post($request);
        }

        return new Response(405, [], json_encode(["status" => "error", "message" => "Method Not Allowed"]));
    }

    private function get(ServerRequestInterface $request): ResponseInterface 
    {
        try {
            $users = $this->users->getAll();
            return new Response(200, [], json_encode(["users" => $users]));
        } catch (\Throwable $e) {
            var_dump($e);
            return new Response(500, [], json_encode(["status" => "error", "message" => "Failed to load"]));
        } 
    }

    private function post(ServerRequestInterface $request): ResponseInterface
    {
        $rawBody = $request->getBody()->getContents();

        $parsedBody = json_decode($rawBody, true);

        $type = $parsedBody["type"];
        $data = $parsedBody["data"];

        $headers = [];

        if ($type === "sign-up") {
            if (Functions::array_all($data, fn($value) => $value !== "")) {
                $name = filter_var($data["name"], FILTER_SANITIZE_STRING);
                $lastname = filter_var($data["lastname"], FILTER_SANITIZE_STRING);
                $phoneNumber = filter_var($data["phone-number"], FILTER_SANITIZE_NUMBER_INT);

                $password = $data["password"];
                $passwordConf = $data["password-confirmation"];

                if ($password !== $passwordConf) {
                    return new Response(400, [], json_encode(["status" => "error", "message" => "Password didn't match with password confirmation"]));
                }

                $headers = ["Set-Cookie" => "token=" . JWTHelper::getJWT() . ";HttpOnly"];

                try {
                    if ($this->users->create($name, $lastname, $phoneNumber, $password)) {
                        return new Response(200, $headers, json_encode(["status" => "success", "message" => "Successfully added user"]));
                    }
                } catch (\Throwable $e) {
                    // Maybe log it to some file
                    var_dump($e);
                } 
                    
                return new Response(500, [], json_encode(["status" => "error", "message" => "Failed to save"]));
            } 

            return new Response(400, [], json_encode(["status" => "error", "message" => "Fields shoudn't be empty"]));
        } else if ($type === "auth") {
            var_dump($request->getHeader("Authorization"), $request->getCookieParams());
            var_dump($_COOKIE);
            $cookies = [
                // $_COOKIE["token"] ?? null, $_SERVER["HTTP_COOKIE"] ?? null, $_SERVER["Authorization"] ?? null, !$_SERVER["Cookie"] ?? null
            ];
            $validCookies = array_filter($cookies, fn($cookie) => $cookie !== null);

            if (count($validCookies) === 0) {
                return new Response(401, [], json_encode(["status" => "error", "message" => "Unauthorized access"]));
            } 

            $payload = (array) JWT::decode($validCookies[0], new Key($_ENV["SECRET_KEY"], $_ENV["ALGORITHM"]));

            if (!JWTHelper::isValidJWTPayload($payload)) {
                return new Response(401, [], json_encode(["status" => "error", "message" => "Unauthorized access"]));
            } 

            return new Response(401, [], json_encode(["status" => "error", "message" => "Unauthorized access"]));
        }

        return new Response(400, [], json_encode(["status" => "error", "message" => "Bad Request"]));
    }
}
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
                    return new Response(401, [], json_encode(["status" => "error", "message" => "Password didn't match with password confirmation"]));
                } else if (!$name || !$lastname || $phoneNumber) {
                    return new Response(401, [], json_encode(["status" => "error", "message" => "Invalid data"]));
                }

                $setCookieHeader = ["Set-Cookie" => "token=" . JWTHelper::getJWT() . ";HttpOnly"];

                try {
                    if ($this->users->create($name, $lastname, $phoneNumber, $password)) {
                        return new Response(200, $setCookieHeader, json_encode(["status" => "success", "message" => "Successfully added user"]));
                    }
                } catch (\Throwable $e) {
                    // Maybe log it to some file
                    var_dump($e);
                } 
                    
                return new Response(500, [], json_encode(["status" => "error", "message" => "Failed to save"]));
            } 

            return new Response(401, [], json_encode(["status" => "error", "message" => "Fields shoudn't be empty"]));
        } else if ($type === "login") {
            if (Functions::array_all($data, fn($value) => $value !== "")) {
                $name = filter_var($data["name"], FILTER_SANITIZE_STRING);
                $lastname = filter_var($data["lastname"], FILTER_SANITIZE_STRING);

                $password = $data["password"];

                if (!$name || !$lastname) {
                    return new Response(401, [], json_encode(["status" => "error", "message" => "Invalid data"]));
                }

                $this->users->get($name, $lastname, $password);
            }

            return new Response(401, [], json_encode(["status" => "error", "message" => "Fields shoudn't be empty"]));
        } else if ($type === "auth") {
            $cookie = $request->getCookieParams()["token"] ?? false;

            if ($cookie == false) {
                return new Response(401, [], json_encode(["status" => "error", "message" => "Unauthorized access"]));
            } 

            try {
                $payload = (array) JWT::decode($cookie, new Key($_ENV["SECRET_KEY"], $_ENV["ALGORITHM"]));
            } catch (\Throwable $e) {
                return new Response(401, [], json_encode(["status" => "error", "message" => "Unauthorized access"]));
            }
            
            if (!JWTHelper::isValidJWTPayload($payload)) {
                return new Response(401, [], json_encode(["status" => "error", "message" => "Unauthorized access"]));
            } 

            return new Response(401, [], json_encode(["status" => "error", "message" => "Unauthorized access"]));
        }

        return new Response(400, [], json_encode(["status" => "error", "message" => "Bad Request"]));
    }
}
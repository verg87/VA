<?php

declare(strict_types=1);

namespace App\Controllers;

require_once __DIR__ . "\\..\\..\\..\\vendor\\autoload.php";

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use App\Models\User;
use App\Models\RefreshSession;
use App\Helpers\JWTHelper;
use App\Helpers\Functions;
use App\Helpers\CookieManager;
use App\Responses\ResponseFactory;

class UsersController 
{
    public function __construct(private User $user, private RefreshSession $refreshSession) 
    {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() === "GET") {
            return $this->get($request);
        } else if ($request->getMethod() === "POST") {
            return $this->post($request);
        }

        return ResponseFactory::create(405)();
    }

    private function get(ServerRequestInterface $request): ResponseInterface 
    {
        try {
            $users = $this->user->getAll();
            return ResponseFactory::create(200)(data: $users);
        } catch (\Throwable $e) {
            var_dump($e);
            return ResponseFactory::create(500)(message: "Failed to load");
        } 
    }

    private function post(ServerRequestInterface $request): ResponseInterface
    {
        $rawBody = $request->getBody()->getContents();

        $parsedBody = json_decode($rawBody, true);

        $type = $parsedBody["type"];
        $data = $parsedBody["data"];

        $ipAddress = isset($request->getServerParams()['HTTP_CLIENT_IP']) 
            ? $request->getServerParams()['HTTP_CLIENT_IP'] 
            : (isset($request->getServerParams()['HTTP_X_FORWARDED_FOR']) 
                ? $request->getServerParams()['HTTP_X_FORWARDED_FOR'] 
                : $request->getServerParams()['REMOTE_ADDR']);

        $userAgent = $request->getHeader("User-Agent")[0] ?? "";

        if ($type === "sign-up") {
            if (Functions::array_all($data, fn($value) => $value !== "")) {
                list(
                    "name" => $name, 
                    "lastname" => $lastname, 
                    "phone-number" => $phoneNumber, 
                    "password" => $password, 
                    "password-confirmation" => $passwordConf
                ) = $data;

                if ($password !== $passwordConf) {
                    return ResponseFactory::create(401)(message: "Password didn't match with password confirmation");
                } 

                try {
                    if ($this->user->create($name, $lastname, $phoneNumber, $password)) {
                        $userId = $this->user->getLatestUserId();

                        $cookie = CookieManager::withInfo($userId, $userAgent, $ipAddress);

                        return ResponseFactory::create(200)(headers: $cookie->create(), message: "Successfully added user");
                    }
                } catch (\Throwable $e) {
                    // Maybe log it to some file
                    var_dump($e);
                    return ResponseFactory::create(500)(message: "Failed to save");
                } 
                    
                return ResponseFactory::create(401)(message: "Invalid data");
            } 

            return ResponseFactory::create(401)(message: "Fields shouldn't be empty");
        } else if ($type === "login") {
            if (Functions::array_all($data, fn($value) => $value !== "")) {
                list(
                    "phone-number" => $phoneNumber, 
                    "password" => $password, 
                ) = $data;

                $user = $this->user->get($phoneNumber, $password);

                if ($user === false) {
                    return ResponseFactory::create(401)(message: "Invalid data");
                } else if (count($user) === 0) {
                    return ResponseFactory::create(404)(message: "No such user");
                }

                $userId = $user["id"];
                unset($user["id"]);

                $cookie = CookieManager::withInfo($userId, $userAgent, $ipAddress);

                return ResponseFactory::create(200)(headers: $cookie->create(), data: $user);
            }

            return ResponseFactory::create(401)(message: "Fields shouldn't be empty");
        } else if ($type === "auth") {
            $cookie = $request->getCookieParams()["access-token"] ?? false;

            if ($cookie == false) {
                return ResponseFactory::create(401)();
            } 

            try {
                $payload = (array) JWT::decode($cookie, new Key($_ENV["SECRET_KEY"], $_ENV["ALGORITHM"]));
            } catch (\Throwable $e) {
                return ResponseFactory::create(401)();
            }
            
            if (!JWTHelper::isValidJWTPayload($payload)) {
                return ResponseFactory::create(401)();
            } 

            return ResponseFactory::create(200)(message: "Access granted");
        } else if ($type === "refresh-token") {
            $cookie = $request->getCookieParams()["refresh-token"] ?? false;

            if ($cookie == false) {
                return ResponseFactory::create(401)();
            } 

            try {
                $payload = (array) JWT::decode($cookie, new Key($_ENV["SECRET_KEY"], $_ENV["ALGORITHM"]));
            } catch (\Throwable $e) {
                return ResponseFactory::create(401)();
            }
            
            if (!JWTHelper::isValidJWTPayload($payload)) {
                return ResponseFactory::create(401)();
            } 

            if (!count($this->refreshSession->get($payload["jti"]))) {
                return ResponseFactory::create(401)();
            } 

            $cookie = CookieManager::withInfo($payload["sub"], $userAgent, $ipAddress);
            return ResponseFactory::create(200)(headers: $cookie->create(), message: "Tokens updated");
        } else if ($type === "log-out") {

        }

        return ResponseFactory::create(400)();
    }
}
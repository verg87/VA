<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use App\Controller;
use App\Models\User;
use App\Helpers\Functions;
use App\Helpers\CookieManager;
use App\Responses\ResponseFactory;

class SignUpController extends Controller
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
        list("data" => $data, "userAgent" => $userAgent, "ipAddress" => $ipAddress) = $this->requestInfo($request);

        if (Functions::array_all($data, fn($value) => $value !== "")) {
            list(
                "email" => $email,
                "phone-number" => $phoneNumber, 
                "name" => $name, 
                "lastname" => $lastname, 
                "password" => $password, 
                "password-confirmation" => $passwordConf
            ) = $data;

            if ($password !== $passwordConf) {
                return ResponseFactory::create(400)(message: "Password didn't match with password confirmation");
            } 

            try {
                if ($this->user->create($name, $lastname, $email, $phoneNumber, $password)) {
                    $userId = $this->user->getLatestUserId();

                    $cookie = CookieManager::withInfo($userId, $userAgent, $ipAddress);

                    return ResponseFactory::create(201)(headers: $cookie->create(), message: "Successfully added user");
                }
            } catch (\Throwable $e) {
                if ($e->getCode() === "23000") {
                    if (str_contains($e->getMessage(), "users.email")) {
                        return ResponseFactory::create(400)(message: "A user with this email already exists");
                    } else if (str_contains($e->getMessage(), "users.phone_number")) {
                        return ResponseFactory::create(400)(message: "A user with this phone number already exists");
                    } 
                  
                    return ResponseFactory::create(400)(message: "A user with such credentials already exists");
                }
                // Maybe log it to some file
                var_dump($e->getMessage());

                return ResponseFactory::create(500)(message: "Failed to save");
            } 
                    
            return ResponseFactory::create(400)(message: "Invalid data");
        } 

        return ResponseFactory::create(400)(message: "Fields shouldn't be empty");
    }
}
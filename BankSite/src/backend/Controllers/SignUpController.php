<?php

declare(strict_types=1);

namespace App\Controllers;

require_once __DIR__ . "\\..\\..\\..\\vendor\\autoload.php";

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
    }
}
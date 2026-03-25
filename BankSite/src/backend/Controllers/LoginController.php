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

class LoginController extends Controller
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
    }
}
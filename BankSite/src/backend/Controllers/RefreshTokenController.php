<?php

declare(strict_types=1);

namespace App\Controllers;

require_once __DIR__ . "\\..\\..\\..\\vendor\\autoload.php";

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use App\Controller;
use App\Models\RefreshSession;
use App\Helpers\CookieManager;
use App\Responses\ResponseFactory;

class RefreshTokenController extends Controller
{
    public function __construct(private RefreshSession $refreshSession)
    {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return $this->post($request);
    }

    private function post(ServerRequestInterface $request): ResponseInterface
    {
        list("userAgent" => $userAgent, "ipAddress" => $ipAddress) = $this->requestInfo($request);

        $cookie = $request->getCookieParams()["refresh-token"] ?? false;

        if ($cookie == false) {
            return ResponseFactory::create(401)();
        } 

        try {
            $payload = (array) JWT::decode($cookie, new Key($_ENV["SECRET_KEY"], $_ENV["ALGORITHM"]));
        } catch (\Throwable $e) {
            return ResponseFactory::create(401)();
        }

        $refreshSessionItem = $this->refreshSession->get($payload["jti"]);

        if (gettype($refreshSessionItem) === "boolean" || !count($refreshSessionItem)) {
            return ResponseFactory::create(401)();
        } else if ($refreshSessionItem["is_revoked"]) {
            $this->refreshSession->delete($refreshSessionItem["user_id"]);
            return ResponseFactory::create(401)();
        }

        $cookie = CookieManager::withInfo($payload["sub"], $userAgent, $ipAddress);
        return ResponseFactory::create(200)(headers: $cookie->create(), message: "Tokens updated");
    }
}
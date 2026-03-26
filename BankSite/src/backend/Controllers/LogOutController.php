<?php

declare(strict_types=1);

namespace App\Controllers;

require_once __DIR__ . "\\..\\..\\..\\vendor\\autoload.php";

use App\Models\RefreshSession;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use App\Controller;
use App\Helpers\CookieManager;
use App\Responses\ResponseFactory;

class LogOutController extends Controller
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
            return ResponseFactory::create(400)();
        }

        try {
            $payload = (array) JWT::decode($cookie, new Key($_ENV["SECRET_KEY"], $_ENV["ALGORITHM"]));
        } catch (\Throwable $e) {
            return ResponseFactory::create(401)();
        }

        if (!$this->refreshSession->update($payload["jti"])) {
            return ResponseFactory::create(500)();
        }
        $refreshSessionItem = $this->refreshSession->get($payload["jti"]);
        var_dump($refreshSessionItem);

        $cookie = CookieManager::withInfo($payload["sub"], $userAgent, $ipAddress);
        return ResponseFactory::create(302)(headers: $cookie->create(true));
    }
}
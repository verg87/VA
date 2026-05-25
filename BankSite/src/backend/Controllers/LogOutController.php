<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\RefreshSession;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use App\Controller;
use App\Vault\Vault;
use App\Helpers\CookieManager;
use App\Responses\ResponseFactory;
use App\Responses\LoggedResponse;

class LogOutController extends Controller
{
    public function __construct(private RefreshSession $refreshSession, private Vault $vault)
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
            $payload = (array) JWT::decode($cookie, new Key($this->vault->getKV("jwtkey"), $_ENV["ALGORITHM"]));
        } catch (\Throwable $e) {
            return ResponseFactory::create(401)();
        }

        if (!$this->refreshSession->update($payload["jti"])) {
            $e = new Exception("There is no such jti as " . $payload["jti"] . ". Perhaps it is an old jti that have been deleted");
            return (new LoggedResponse($e, $request))();
        }

        $cookie = CookieManager::withInfo($payload["sub"], $userAgent, $ipAddress);
        return ResponseFactory::create(302)(headers: $cookie->create(true));
    }
}
<?php

declare(strict_types=1);

namespace App;

use App\Model;
use App\Vault\Vault;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Nyholm\Psr7\Response;

abstract class Controller
{
    public function __construct(Model|Vault ...$models)
    {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return new Response(200);
    }

    protected function validateBankRequest(array $data, array $attr): bool
    {
        return 
            isset($attr["user"]) && gettype($attr["user"]) === "array" &&
            $attr["user"]["id"] === (int) ($data["user_id"] ?? -1);
    }

    protected function requestInfo(ServerRequestInterface $request): array
    {
        $rawBody = $request->getBody()->getContents();
        $parsedBody = json_decode($rawBody, true);

        $data = $parsedBody["data"] ?? [];
        $userAgent = $request->getHeader("User-Agent")[0] ?? "";

        $ipAddress = isset($request->getServerParams()["REMOTE_ADDR"]) 
            ? $request->getServerParams()["REMOTE_ADDR"] 
            : (isset($request->getServerParams()["HTTP_X_FORWARDED_FOR"]) 
                ? $request->getServerParams()["HTTP_X_FORWARDED_FOR"] 
                : $request->getServerParams()["HTTP_CLIENT_IP"]);

        return [
            "attributes" => $request->getAttributes(),
            "query" => $request->getQueryParams(),
            "data" => $data,
            "userAgent" => $userAgent,
            "ipAddress" => $ipAddress,
        ];
    }
}
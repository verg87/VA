<?php

declare(strict_types=1);

namespace App\Traits;

use Psr\Http\Message\ServerRequestInterface;

trait IpRetrieverTrait
{
    private function getIpAddress(ServerRequestInterface $request): string
    {
        return isset($request->getServerParams()["REMOTE_ADDR"]) 
            ? $request->getServerParams()["REMOTE_ADDR"] 
            : (isset($request->getServerParams()["HTTP_X_FORWARDED_FOR"]) 
                ? $request->getServerParams()["HTTP_X_FORWARDED_FOR"] 
                : $request->getServerParams()["HTTP_CLIENT_IP"]);
    }
}
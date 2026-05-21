<?php

declare(strict_types = 1);

namespace App;

class Config
{
    public array $config = [];

    public function __construct(array $env)
    {
        $this->config = [
            "db" => [
                "host"     => $env["DB_HOST"],
                "user"     => $env["DB_USER"],
                "pass"     => $env["DB_PASSWORD"],
                "driver"   => $env["DB_DRIVER"] ?? "pdo_mysql",
                "database" => $env["DB_NAME"],
            ],
            "vault" => [
                "unsealKey1" => $env["UNSEAL_KEY_1"] ?? "",
                "unsealKey2" => $env["UNSEAL_KEY_2"] ?? "",
                "unsealKey3" => $env["UNSEAL_KEY_3"] ?? "",
                "rootToken"  => $env["ROOT_TOKEN"] ?? "",
            ]
        ];
    }
}
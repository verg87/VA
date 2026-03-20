<?php

declare(strict_types = 1);

namespace App;

class Config
{
    public array $config = [];

    public function __construct(array $env)
    {
        $this->config = [
            "host"     => $env["DB_HOST"],
            "user"     => $env["DB_USER"],
            "pass"     => $env["DB_PASSWORD"],
            "driver"   => $env["DB_DRIVER"] ?? "mysql",
            "database" => $env["DB_NAME"],
        ];
    }
}
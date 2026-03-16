<?php

declare(strict_types = 1);

namespace App;

class Config
{
    protected array $config = [];

    public function __construct(array $env)
    {
        $this->config = [
            "host"     => $env["DB_HOST"],
            "user"     => $env["DB_USER"],
            "pass"     => $env["DB_PASSOWRD"],
            "driver"   => $env["DB_DRIVER"] ?? "mysql",
        ];
    }

    public function __get(string $name)
    {
        return $this->config[$name] ?? null;
    }
}
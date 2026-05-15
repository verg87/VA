<?php

use App\DB;
use App\Config;
use App\Vault\Vault;
use Symfony\Component\Dotenv\Dotenv;

use DI\Container;
use Psr\Container\ContainerInterface;

$dotenv = new Dotenv();
$dotenv->overload(__DIR__ . "\\..\\..\\.env", __DIR__ . "\\..\\..\\.dev.env");

$container = new Container();

$container->set(Config::class, function () {
    return new Config($_ENV);
});

$container->set(DB::class, function (ContainerInterface $container) {
    $config = $container->get(Config::class);
    return DB::getInstance($config->config);
});

$container->set(Vault::class, function (ContainerInterface $container) {
    $config = $container->get(Config::class);
    return new Vault($config->config);
});

return $container;
<?php

declare(strict_types=1);

namespace App;

require __DIR__ . "\\..\\..\\vendor\\autoload.php";
$container = require_once __DIR__ . "\\Container.php";

ini_set('display_errors', 'stderr');

use League\Route\Router;
use League\Route\RouteGroup;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Factory\Psr17Factory;

use Spiral\RoadRunner\Worker;
use Spiral\RoadRunner\Http\PSR7Worker;

use App\Controllers\CardsController;
use App\Controllers\DepositController;
use App\Controllers\TransferController;
use App\Controllers\TransactionsController;

use App\Controllers\AccessUserController;
use App\Controllers\AuthController;
use App\Controllers\LoginController;
use App\Controllers\SignUpController;
use App\Controllers\LogOutController;
use App\Controllers\RefreshTokenController;

use App\Middleware\AuthMiddleware;
use App\Middleware\ErrorHandlerMiddleware;
use App\Strategies\ErrorHandlerStrategy;

$worker = Worker::create();

$factory = new Psr17Factory();
$psr7 = new PSR7Worker($worker, $factory, $factory, $factory);

$router = new Router();

$router->setStrategy(new ErrorHandlerStrategy());

$router->group("/api/bank", function (RouteGroup $router) use ($container) {
    $router->get("/cards", $container->get(CardsController::class));
    $router->post("/cards", $container->get(CardsController::class));
    $router->get("/transactions", $container->get(TransactionsController::class));

    $router->post("/deposit", $container->get(DepositController::class));
    $router->post("/transfer", $container->get(TransferController::class));
})->middleware($container->get(AuthMiddleware::class));

$router->get("/api/users/", $container->get(AccessUserController::class));
$router->post("/api/users/", $container->get(AccessUserController::class));
$router->post("/api/users/sign-up", $container->get(SignUpController::class));
$router->post("/api/users/login", $container->get(LoginController::class));
$router->post("/api/users/auth", $container->get(AuthController::class));
$router->post("/api/users/refresh-token", $container->get(RefreshTokenController::class));
$router->post("/api/users/log-out", $container->get(LogOutController::class));

while (true) {
    try {
        $request = $psr7->waitRequest();
        if ($request === null) {
            break;
        }
    } catch (\Throwable $e) {
        $psr7->respond(new Response(400));
        continue;
    }

    $psr7->respond($router->dispatch($request));
}
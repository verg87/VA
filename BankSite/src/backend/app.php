<?php

declare(strict_types=1);

namespace App;

require __DIR__ . '\\..\\..\\vendor\\autoload.php';
require_once __DIR__ . "\\Helpers\\DBInstance.php";

ini_set('display_errors', 'stderr');

use League\Route\Router;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Factory\Psr17Factory;

use Spiral\RoadRunner\Worker;
use Spiral\RoadRunner\Http\PSR7Worker;

use App\Controllers\CardsController;

use App\Controllers\AccessUserController;
use App\Controllers\AuthController;
use App\Controllers\LoginController;
use App\Controllers\SignUpController;
use App\Controllers\LogOutController;
use App\Controllers\RefreshTokenController;

use App\Models\Card;

use App\Models\RefreshSession;
use App\Models\User;

$worker = Worker::create();

$factory = new Psr17Factory();
$psr7 = new PSR7Worker($worker, $factory, $factory, $factory);

$router = new Router();

$router->get("/api/bank/cards", new CardsController(new Card()));
$router->post("/api/bank/cards", new CardsController(new Card()));

$router->post("/api/users/", new AccessUserController(new User()));
$router->post("/api/users/sign-up", new SignUpController(new User()));
$router->post("/api/users/login", new LoginController(new User()));
$router->post("/api/users/auth", new AuthController(new User()));
$router->post("/api/users/refresh-token", new RefreshTokenController(new RefreshSession()));
$router->post("/api/users/log-out", new LogOutController(new RefreshSession()));

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

    try {
        $psr7->respond($router->dispatch($request));
    } catch (\Throwable $e) {
        $psr7->respond(new Response(500, [], 'Something Went Wrong!'));
        $psr7->getWorker()->error((string)$e);
    }
}
<?php

declare(strict_types=1);

namespace App;

require __DIR__ . '\\..\\..\\vendor\\autoload.php';

ini_set('display_errors', 'stderr');

use App\DB;
use App\Config;
use App\Vault\Vault;
use Symfony\Component\Dotenv\Dotenv;
use League\Route\Router;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Factory\Psr17Factory;

use Spiral\RoadRunner\Worker;
use Spiral\RoadRunner\Http\PSR7Worker;

use App\Controllers\CardsController;
use App\Controllers\DepositController;

use App\Controllers\AccessUserController;
use App\Controllers\AuthController;
use App\Controllers\LoginController;
use App\Controllers\SignUpController;
use App\Controllers\LogOutController;
use App\Controllers\RefreshTokenController;

use App\Models\Card;
use App\Models\Transaction;

use App\Models\RefreshSession;
use App\Models\User;

$dotenv = new Dotenv();
$dotenv->overload(__DIR__ . "\\..\\..\\.env", __DIR__ . "\\..\\..\\.dev.env");

$config = (new Config($_ENV))->config;
$db = DB::getInstance($config);
$vault = new Vault($config);

$worker = Worker::create();

$factory = new Psr17Factory();
$psr7 = new PSR7Worker($worker, $factory, $factory, $factory);

$router = new Router();

$router->get("/api/bank/cards", new CardsController(new Card($db, $vault)));
$router->post("/api/bank/cards", new CardsController(new Card($db, $vault)));

$router->post("/api/bank/deposit", new DepositController(new Card($db, $vault), new Transaction($db)));

$router->post("/api/users/", new AccessUserController(new User($db), $vault));
$router->post("/api/users/sign-up", new SignUpController(new User($db)));
$router->post("/api/users/login", new LoginController(new User($db)));
$router->post("/api/users/auth", new AuthController(new User($db), $vault));
$router->post("/api/users/refresh-token", new RefreshTokenController(new RefreshSession($db, $vault), $vault));
$router->post("/api/users/log-out", new LogOutController(new RefreshSession($db, $vault), $vault));

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
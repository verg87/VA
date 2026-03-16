<?php

namespace App;

require __DIR__ . '\\..\\..\\vendor\\autoload.php';

use League\Route\Router;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Factory\Psr17Factory;

use Spiral\RoadRunner\Worker;
use Spiral\RoadRunner\Http\PSR7Worker;

use Symfony\Component\Dotenv\Dotenv;

use App\Controllers\UsersController;
use App\Models\Users;
use App\DB;

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . "\\..\\..\\.env", __DIR__ . "\\..\\..\\.dev.env");

define("DBI", new DB((new Config($_ENV))->config));

$worker = Worker::create();

$factory = new Psr17Factory();
$psr7 = new PSR7Worker($worker, $factory, $factory, $factory);

$router = new Router();

$router->get("/users", new UsersController(new Users()));
$router->post("/users", new UsersController(new Users()));

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
        $response = $router->dispatch($request);

        $psr7->respond($response);
    } catch (\Throwable $e) {
        $psr7->respond(new Response(500, [], 'Something Went Wrong!'));
        $psr7->getWorker()->error((string)$e);
    }
}
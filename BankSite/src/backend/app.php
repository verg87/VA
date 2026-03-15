<?php

require __DIR__ . '\\..\\..\\vendor\\autoload.php';

use League\Route\Router;
use League\Route\Strategy\JsonStrategy;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Factory\Psr17Factory;

use Spiral\RoadRunner\Worker;
use Spiral\RoadRunner\Http\PSR7Worker;
$worker = Worker::create();

$factory = new Psr17Factory();
$psr7 = new PSR7Worker($worker, $factory, $factory, $factory);

$strategy = new JsonStrategy($factory);

$router = new Router();
$router->setStrategy($strategy);

try {
    $db = new PDO("mysql:host=localhost", "root", $_ENV["DB_PASSWORD"]); 
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $db->exec("CREATE DATABASE IF NOT EXISTS bank DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $db->exec("USE bank");
} catch (PDOException $e) {
    
}

$router->get("/users", static function(ServerRequest $request) use($db): array {
    // $stmt = $db->prepare("SELECT * FROM users");
    // $stmt->execute();

    // $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        // "users" => $users
    ];
});

$router->post("/users", static function(ServerRequest $request): array {
    $body = $request->getParsedBody();
    return [

    ];
});

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
        var_dump($request->getParsedBody());
    } catch (\Throwable $e) {
        $psr7->respond(new Response(500, [], 'Something Went Wrong!'));
        $psr7->getWorker()->error((string)$e);
    }
}
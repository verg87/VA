<?php

declare(strict_types=1);

namespace App\Services;

require __DIR__ . '\\..\\..\\..\\vendor\\autoload.php';

use Symfony\Component\Dotenv\Dotenv; 

use App\DB;
use App\Config;

use App\Services\Workers\RefreshSessionsWorker;
use App\Models\RefreshSession;

$dotenv = new Dotenv();
$dotenv->overload(__DIR__ . "\\..\\..\\..\\.env", __DIR__ . "\\..\\..\\..\\.dev.env");

$config = (new Config($_ENV))->config;
$db = DB::getInstance($config);

$worker = new RefreshSessionsWorker(new RefreshSession($db));
$worker->run();
<?php

declare(strict_types=1);

namespace App\Services;

require __DIR__ . '\\..\\..\\..\\vendor\\autoload.php';

use Symfony\Component\Dotenv\Dotenv; 

use App\DB;
use App\Config;

use App\Services\Workers\RefreshSessionsCleanerWorker;
use App\Models\RefreshSession;

$dotenv = new Dotenv();
$dotenv->overload(__DIR__ . "\\..\\..\\..\\.env", __DIR__ . "\\..\\..\\..\\.dev.env");

$config = (new Config($_ENV))->config;
$db = new DB($config);

$worker = new RefreshSessionsCleanerWorker(new RefreshSession($db));
$worker->run();
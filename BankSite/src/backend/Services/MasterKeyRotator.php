<?php

declare(strict_types=1);

namespace App\Services;

require __DIR__ . '\\..\\..\\..\\vendor\\autoload.php';

$container = require_once __DIR__ . "\\..\\Container.php";

use App\Services\Workers\MasterKeyWorker;

$worker = $container->get(MasterKeyWorker::class);
$worker->run();
<?php

declare(strict_types=1);

namespace App\Services;

require __DIR__ . '\\..\\..\\..\\vendor\\autoload.php';
require_once __DIR__ . "\\..\\Helpers\\DBInstance.php";

use Symfony\Component\Dotenv\Dotenv;

use App\Services\Workers\RefreshSessionsCleanerWorker;
use App\Models\RefreshSession;

$dotenv = new Dotenv();
$dotenv->overload(__DIR__ . "\\..\\..\\..\\.env", __DIR__ . "\\..\\..\\..\\.dev.env");

$worker = new RefreshSessionsCleanerWorker(new RefreshSession());
$worker->run();
<?php

declare(strict_types=1);

namespace App\Helpers;

use Symfony\Component\Dotenv\Dotenv;

use App\DB;
use App\Config;

$dotenv = new Dotenv();
$dotenv->overload(__DIR__ . "\\..\\..\\..\\.env", __DIR__ . "\\..\\..\\..\\.dev.env");

define("DBI", new DB((new Config($_ENV))->config));
<?php

namespace App\Helpers;

use Monolog\Logger;
use Throwable;

use App\Log;

trait LoggerTrait
{
    private string $file = "errors.log";

    public function log(Throwable $exception, string $name, string $method, string $path): void
    {
        $log = Log::create($name, $this->file, Logger::ERROR);

        $message = $exception->getMessage();
        $file = $exception->getFile();
        $line = $exception->getLine();

        $logMessage = "{$method}, {$path}, {$message}, {$file}, {$line}" . "\n";

        $log->error($logMessage);
    }
}
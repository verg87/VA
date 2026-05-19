<?php

namespace App\Helpers;

use Monolog\Logger;
use Respect\Validation\Exceptions\ValidationException;
use Respect\Validation\Exceptions\ComponentException;
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

        if ($exception instanceof ValidationException || $exception instanceof ComponentException) {
            $file = $exception->getTrace()[1]["file"] ?? $file;
            $line = $exception->getTrace()[1]["line"] ?? $line;
        }

        // var_dump($exception->getTraceAsString());

        $logMessage = "{$method}, {$path}, {$message}, {$file}, {$line}" . "\n";

        $log->error($logMessage);
    }
}
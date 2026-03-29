<?php

namespace App;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Log
{
    private Logger $logger;
    private string $defaultLogFilesPath = __DIR__ . "\\log\\";

    public function __construct(string $name, string $file, int $level = Logger::DEBUG)
    {
        $this->logger = new Logger($name);
        $this->logger->setTimezone(new \DateTimeZone('UTC'));
        $this->logger->pushHandler(new StreamHandler($this->defaultLogFilesPath . $file, $level));
    }

    public function __call(string $name, array $arguments)
    {
        return call_user_func_array([$this->logger, $name], $arguments);
    }
}

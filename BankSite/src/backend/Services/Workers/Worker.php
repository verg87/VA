<?php

declare(strict_types=1);

namespace App\Services\Workers;

use App\Log;
use Throwable;

abstract class Worker
{
    private string $name;
    private int $sleepTime;
    private Log $log;

    public function __construct(string $name, int $sleepTime)
    {
        $this->name = $name;
        $this->sleepTime = $sleepTime;
        $this->log = new Log($name . "_logger", $name . ".log");
    }

    public abstract function work(): void;

    public function run(): never
    {
        while (true) {
            try {
                $this->work();
            } catch (Throwable $e) {
                $this->log->error($e);
            }
            sleep($this->sleepTime);
        }
    }

    public function log(string $message): void
    {
        $this->log->info($message);
    }
}

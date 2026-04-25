<?php

declare(strict_types=1);

namespace App\Services\Workers;

use App\Log;
use Throwable;

abstract class Worker
{
    private string $name;
    private int $sleepTime;
    private bool $runOnFirstStart = true;
    private int $numberOfRetriesAfterInitialFailure = 0;
    private int $sleepTimeAfterRetry = 60;
    private Log $log;

    public function __construct(
        string $name, int $sleepTime, bool $runOnFirstStart = true, int $numberOfRetriesAfterInitialFailure = 0
    )
    {
        $this->name = $name;
        $this->sleepTime = $sleepTime;
        $this->runOnFirstStart = $runOnFirstStart;
        $this->numberOfRetriesAfterInitialFailure = $numberOfRetriesAfterInitialFailure;
        $this->log = new Log($name . "_logger", $name . ".log");
    }

    public abstract function work(): bool;

    public function run(): never
    {
        while (true) {
            if (!$this->runOnFirstStart) {
                $this->runOnFirstStart = true;
                sleep($this->sleepTime);
            } 

            try {
                if (!$this->work() && $this->numberOfRetriesAfterInitialFailure > 0) {
                    $this->log("Worker failed, retrying in " . $this->sleepTimeAfterRetry . " seconds...");
                    sleep($this->sleepTimeAfterRetry);

                    for ($i = 0; $i < $this->numberOfRetriesAfterInitialFailure; $i++) {
                        if ($this->work()) {
                            break;
                        }

                        if ($i === $this->numberOfRetriesAfterInitialFailure - 1) {
                            $this->log("Worker failed after " . $this->numberOfRetriesAfterInitialFailure . " retries, waiting for the next cycle...");
                            break;
                        }
                        
                        $this->log("Worker failed, retrying in " . $this->sleepTimeAfterRetry . " seconds...");
                        sleep($this->sleepTimeAfterRetry);
                    }
                } 
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

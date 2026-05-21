<?php

declare(strict_types=1);

namespace App;

use PDO;

use App\Helpers\LoggerTrait;

class DB
{
    use LoggerTrait;
    private static array $instances = [];

    final private function __construct(private PDO $pdo, public string $name) {}

    private static function createPDO(array $config): PDO
    {
        $defaultOptions = [
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        try {
            $dbConfig = $config["db"];

            return new PDO(
                $dbConfig['driver'] . ':host=' . $dbConfig['host'],
                $dbConfig['user'],
                $dbConfig['pass'],
                $dbConfig['options'] ?? $defaultOptions
            );
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int) $e->getCode());
        }
    }

    public static function getInstance(array $config): static
    {
        $name = $config["db"]["database"] ?? "";

        if (empty($name)) {
            throw new \InvalidArgumentException("Database name must be specified in the configuration");
        }

        if (
            !isset(static::$instances[$name]) ||
            (isset(static::$instances[$name]) && !static::ping($name)) 
        ) {
            static::reconnect($config);
        }

        return (new static(static::$instances[$name], $name));
    }

    public function __call(string $name, array $arguments)
    {
        try {
            return call_user_func_array([$this->pdo, $name], $arguments);
        } catch (\PDOException $e) {
            $this->log($e, "DATABASE", $name, str_replace(["\r", "\n", "\t"], " ", print_r($arguments, true)));
            throw $e;
        }
    }

    private static function ping(string $name): bool
    {
        try {
            static::$instances[$name]->query('SELECT 1');
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    private static function reconnect(array $config): void
    {
        $name = $config["db"]["database"];

        static::$instances[$name] = static::createPDO($config); 
    }
}
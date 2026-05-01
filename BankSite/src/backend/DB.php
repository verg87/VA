<?php

declare(strict_types = 1);

namespace App;

use Exception;
use PDO;

class DB
{
    private static array $instances = [];
    private static array $lastConfig = [];
    private static array $connectedDBs = [];

    final private function __construct(private PDO $pdo, public string $name) {}

    private static function createPDO(array $config): PDO
    {
        $defaultOptions = [
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        try {
            $dbConfig = $config["db"];
            
            // Store the last used config
            static::$lastConfig = $dbConfig; 

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
            !isset(static::$connectedDBs[$name]) || 
            (isset(static::$instances[$name]) && !static::ping()) 
        ) {
            static::reconnect($config);
            static::$connectedDBs[$name] = true;
        }

        return (new static(static::$instances[$name], $name));
    }

    public function __call(string $name, array $arguments)
    {
        try {
            return call_user_func_array([$this->pdo, $name], $arguments);
        } catch (\PDOException $e) {
            // connection error
            if (str_contains($e->getMessage(), "SQLSTATE[HY000]")) { 
                static::reconnect(static::$lastConfig);
                return call_user_func_array([$this->pdo, $name], $arguments);
            }
            throw $e; 
        }
    }

    private static function ping(): bool
    {
        try {
            $name = static::$lastConfig["database"] ?? "";

            if (empty($name)) {
                throw new Exception("Database name not specified");
            }

            static::$instances[$name]->query('SELECT 1');
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    private static function reconnect(array $config): void
    {
        $name = $config["db"]["database"] ?? "";

        if (empty($name)) {
            throw new \InvalidArgumentException("Database name must be specified in the configuration");
        }

        static::$instances[$name] = static::createPDO($config); 
    }
}
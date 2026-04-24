<?php

declare(strict_types = 1);

namespace App;

use PDO;

class DB
{
    private PDO $pdo;

    public function __construct(public array $config)
    {
        $defaultOptions = [
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        try {
            $dbConfig = $config["db"];

            $this->pdo = new PDO(
                $dbConfig['driver'] . ':host=' . $dbConfig['host'],
                $dbConfig['user'],
                $dbConfig['pass'],
                $dbConfig['options'] ?? $defaultOptions
            );
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int) $e->getCode());
        }
    }

    public function __call(string $name, array $arguments)
    {
        return call_user_func_array([$this->pdo, $name], $arguments);
    }
}
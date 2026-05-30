<?php
declare(strict_types=1);

namespace App\Services;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\CacheStorage;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class RateLimiterService
{
    private array $limiters = [];
    private string $configPath = __DIR__ . "\\..\\..\\..\\rate_limiter.yaml";

    public function __construct()
    {
        $config = Yaml::parseFile($this->configPath);
        
        $cache = new FilesystemAdapter(); 
        $storage = new CacheStorage($cache);
        // print_r($config);
        
        foreach ($config["framework"]["rate_limiter"] as $name => $settings) {
            $this->limiters[$name] = new RateLimiterFactory($settings, $storage);
        }
    }

    public function get(string $limiterName): RateLimiterFactory
    {
        if (!isset($this->limiters[$limiterName])) {
            throw new \InvalidArgumentException("Limiter '$limiterName' not found in config.");
        }
        return $this->limiters[$limiterName];
    }
}
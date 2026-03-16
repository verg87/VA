<?php
use Symfony\Component\Dotenv\Dotenv;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once __DIR__ . "\\..\\vendor\\autoload.php";

$dotenv = new Dotenv();

$dotenv->load(__DIR__ . "\\..\\.env", __DIR__ . "\\..\\.dev.env");

const ALLOWED_ORIGIN = "http://localhost:5173";

header("Access-Control-Allow-Origin: " . ALLOWED_ORIGIN);
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

function array_all(array $array, callable $callable) 
{
    foreach ($array as $key => $value) {
        if (! $callable($value, $key))
            return false;
    }
    return true;
}

function getJWT(): string
{
    $time = time();

    $payload = [
        "iss" => "http://localhost:8000",
        "aud" => "http://localhost:5173",
        "iat" => $time,
        "nbf" => $time + (60 * 15)
    ];

    return JWT::encode($payload, $_ENV["SECRET_KEY"], $_ENV["ALGORITHM"]);
}

function isValidJWTPayload(array $payload): bool
{
    $keysDoNotExist = 
        $payload["iss"] ?? false && $payload["aud"] ?? false &&
        $payload["iat"] ?? false && $payload["nbf"] ?? false;

    if (
        $keysDoNotExist ||
        $payload["iss"] !== "http://localhost:8000" || 
        $payload["aud"] !== "http://localhost:5173" ||
        !is_numeric($payload["iat"]) || !is_numeric($payload["nbf"])
    ) {
        return false;
    }

    if (time() >= $payload["nbf"]) {
        return false;
    }

    return true;
}
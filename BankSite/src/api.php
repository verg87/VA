<?php
// run php -S localhost:8000
use Symfony\Component\Dotenv\Dotenv;
use Firebase\JWT\JWT;

require_once __DIR__ . "\\..\\vendor\\autoload.php";

$dotenv = new Dotenv();

$dotenv->load(__DIR__.'\\..\\.env');

const ALLOWED_ORIGIN = "http://localhost:5173";

header("Access-Control-Allow-Origin: " . ALLOWED_ORIGIN);
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// if (isset($_SERVER["HTTP_ORIGIN"]) && $_SERVER["HTTP_ORIGIN"] !== ALLOWED_ORIGIN){
    
// } else {
//     exit;
// }

function array_all(array $array, callable $callable) {
    foreach ($array as $key => $value) {
        if (! $callable($value, $key))
            return false;
    }
    return true;
}

try {
    $db = new PDO("mysql:host=localhost", "root", $_ENV["DB_PASSWORD"]); 
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $db->exec("CREATE DATABASE IF NOT EXISTS bank DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $db->exec("USE bank");
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $db->prepare("SELECT * FROM users");
    $stmt->execute();

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["users" => $users]);
}

if ($method === 'POST') {
    $content = file_get_contents("php://input");
    $data = json_decode($content, true);

    if (array_all($data, fn($value) => $value !== "")) {
        $name = filter_var($data["name"], FILTER_SANITIZE_STRING);
        $lastname = filter_var($data["lastname"], FILTER_SANITIZE_STRING);
        $phoneNumber = filter_var($data["phone-number"], FILTER_SANITIZE_NUMBER_INT);

        $password = $data["password"];
        $passwordConf = $data["password-confirmation"];

        if ($password !== $passwordConf) {
            echo json_encode(["message" => "Password didn't match with password confirmation"]);
        }

        $pwdHash = password_hash($password, PASSWORD_DEFAULT, ["cost" => 12]);

        $createTableSql = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            phone_number VARCHAR(20) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        $db->exec($createTableSql);

        $stmt = $db->prepare("INSERT INTO users (first_name, last_name, phone_number, password) VALUES (:first_name, :last_name, :phone_number, :password)");

        $stmt->bindParam(":first_name", $name);
        $stmt->bindParam(":last_name", $lastname);
        $stmt->bindParam(":phone_number", $phoneNumber);
        $stmt->bindParam(":password", $pwdHash);

        $time = time();

        $key = 'apple-show-quarantine-serendipity-quixotic-mitigate-snow';
        $payload = [
            'iss' => 'http://localhost:8000',
            'aud' => 'http://localhost:5173',
            'iat' => $time,
            'nbf' => $time + (60 * 15)
        ];

        $jwt = JWT::encode($payload, $key, 'HS256');

        header("Set-Cookie: token=" . $jwt . ";HttpOnly");

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "id" => $db->lastInsertId()]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to save"]);
        }
    } else {
        echo json_encode(["message" => "Fields shoudn't be empty"]);
    }
}
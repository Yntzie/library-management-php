<?php
$basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
$autoloadPath = $basePath . "/vendor/autoload.php";

if (file_exists($autoloadPath)) {
  require_once $autoloadPath;
}

if (class_exists(Dotenv\Dotenv::class) && file_exists($basePath . "/.env")) {
  Dotenv\Dotenv::createImmutable($basePath)->safeLoad();
}

$host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: null;
$user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: null;
$pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: "";
$db   = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: null;
$port = (int) ($_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: 3306);

if (!$host || !$user || !$db) {
  die("Database environment variables are not configured.");
}

//connect to database
try {
  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
  $conn = new mysqli($host, $user, $pass, $db, $port);
  $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
  echo "Connection failed: " . $e->getMessage();
}

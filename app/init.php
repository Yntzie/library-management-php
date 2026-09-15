<?php

// BASE PATH (mengarah ke folder utama "TubesPWD")
define("BASE_PATH", dirname(__DIR__));

// Auto-load database
require_once BASE_PATH . "/app/config/database.php";
require_once BASE_PATH . "/app/config/migrations.php";

runMigrations($conn);

// Auto-load helpers jika ada
$helperFiles = glob(BASE_PATH . "/app/helpers/*.php");
foreach ($helperFiles as $file) {
    require_once $file;
}

foreach (glob(BASE_PATH . "/app/models/*.php") as $file) {
    require_once $file;
}

foreach (glob(BASE_PATH . "/app/controllers/*.php") as $file) {
    require_once $file;
}

// Auto start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}



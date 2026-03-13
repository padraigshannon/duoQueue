<?php

$DB_HOST = "localhost";
$DB_NAME = "my_database";
$DB_USER = "duo_queue_admin";
$DB_PASS = "gamingforever123";
$DB_PORT = 3306;

$DB_DSN = "mysql:host=$DB_HOST;dbname=$DB_NAME;port=$DB_PORT;charset=utf8mb4";

try {
    $pdo = new PDO($DB_DSN, $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

?>
<?php

$dsn = "mysql:host=localhost;dbname=admin_pokemaps;charset=utf8mb4";
$username = "pokemaps";
$password = "V54%I~REDACTED";

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Database connection error: " . $e->getMessage());
}

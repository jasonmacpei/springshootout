<?php
// scripts/php/db_connect.php

// Include the central configuration file
require_once __DIR__ . '/../../includes/config.php';

// Create a PDO connection using the settings from config.php for PostgreSQL
$dsn = "pgsql:host=" . DB_HOST . ";dbname=" . DB_NAME;
try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed: " . $e->getMessage());
}
?>
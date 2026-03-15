<?php
// db_connect.php
require_once __DIR__ . '/../../includes/config.php';

$dbHost = DB_HOST;
$dbName = DB_NAME;
$dbUser = DB_USER;
$dbPass = DB_PASS;

$dsn = "pgsql:host=$dbHost;dbname=$dbName";
try {
    $pdo = new PDO($dsn, $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    header('Location: http://www.springshootout.ca/pages/error.html');
    exit;
}
?>

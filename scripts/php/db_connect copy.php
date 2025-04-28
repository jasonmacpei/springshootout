<?php
// db_connect.php
$dbHost = 'localhost';
$dbName = 'lostan6_shootout';
$dbUser = 'lostan6_admin1';
$dbPass = 'J0rdan23!';

$dsn = "pgsql:host=$dbHost;dbname=$dbName";
try {
    $pdo = new PDO($dsn, $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    header('Location: http://www.springshootout.ca/pages/error.html');
    exit;
}
?>

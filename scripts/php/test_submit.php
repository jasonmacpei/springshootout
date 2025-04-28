<?php
// test_submit.php
// Minimal script to insert a team into 'teams' table

// 1. Enable error display for debugging (optional)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Include your DB connection
//    Adjust the relative path as needed:
require __DIR__ . '/db_connect.php';
// Or: require_once '../scripts/php/db_connect.php'; 
// depending on your file structure

try {
    // 3. Get the POST data
    $teamName = $_POST['team_name'] ?? '';

    // Basic validation
    if (empty($teamName)) {
        die("Team name cannot be empty.");
    }

    // 4. Prepare and execute the insert
    $sql = "INSERT INTO teams (team_name) VALUES (:teamName)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':teamName' => $teamName]);

    // 5. Confirm success
    echo "Team inserted successfully! Team name: " . htmlspecialchars($teamName);

} catch (PDOException $e) {
    // If an error occurs, display or log it
    echo "Database error: " . $e->getMessage();
    // You can also log this to a file if you have logging set up
}
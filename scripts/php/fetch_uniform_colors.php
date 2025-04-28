<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the configuration file which sets the error log path
require_once '/home/lostan6/springshootout.ca/includes/config.php';

// fetch_uniform_colors.php
require __DIR__ . '/db_connect.php';

$team_id = isset($_POST['team_id']) ? $_POST['team_id'] : null;
$response = ['home_color' => '', 'away_color' => ''];

if ($team_id) {
    $uniformQuery = "SELECT home_color, away_color FROM team_uniforms WHERE team_id = :team_id";
    $uniformStmt = $pdo->prepare($uniformQuery);
    $uniformStmt->execute(['team_id' => $team_id]);
    $current_uniform = $uniformStmt->fetch(PDO::FETCH_ASSOC);

    if ($current_uniform) {
        $response = $current_uniform;
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>

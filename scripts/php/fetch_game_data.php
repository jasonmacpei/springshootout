<?php
// fetch_game_data.php
require_once '/home/lostan6/springshootout.ca/includes/config.php';
require __DIR__ . '/db_connect.php';

if (isset($_GET['game_id']) && is_numeric($_GET['game_id'])) {
    $game_id = $_GET['game_id'];

    // Use the updated SQL to fetch the game data
    $stmt = $pdo->prepare("
        SELECT  
            s.game_id,
            ht.team_name AS home_team,
            gr1.points_for AS home_score,
            at.team_name AS away_team,
            gr2.points_for AS away_score,
            s.game_time,
            s.game_date,
            s.gym
        FROM schedule s
        JOIN game_results gr1 ON s.game_id = gr1.game_id AND s.home_team_id = gr1.team_id
        JOIN game_results gr2 ON s.game_id = gr2.game_id AND s.away_team_id = gr2.team_id
        JOIN teams ht ON s.home_team_id = ht.team_id
        JOIN teams at ON s.away_team_id = at.team_id
        WHERE s.game_id = :game_id
    ");
    $stmt->execute([':game_id' => $game_id]);
    $gameData = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode($gameData);
} else {
    echo json_encode(['error' => 'No game ID provided.']);
}
?>


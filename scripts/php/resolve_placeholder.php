<?php
declare(strict_types=1);

/**
 * Resolve team placeholder (e.g., "A1", "B2") to actual team based on standings
 * 
 * @param PDO $pdo Database connection
 * @param string $placeholder Placeholder code (e.g., "A1", "B2")
 * @param int $year Tournament year
 * @return array|null Team information or null if not resolvable
 */
function resolveTeamFromPlaceholder(PDO $pdo, string $placeholder, int $year = 2025): ?array {
    if (empty($placeholder) || strlen($placeholder) < 2) {
        return null;
    }
    
    // Parse placeholder format (e.g., "A1" = Pool A, Position 1)
    $poolLetter = substr($placeholder, 0, 1);
    $position = (int)substr($placeholder, 1) - 1; // Convert to zero-based index
    
    // Get pool ID from pool letter
    $poolStmt = $pdo->prepare("
        SELECT pool_id FROM pools WHERE pool_name = :poolName
    ");
    $poolStmt->execute([':poolName' => $poolLetter]);
    $poolResult = $poolStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$poolResult) {
        return null;
    }
    
    $poolId = $poolResult['pool_id'];
    
    // Query to get teams from the specified pool, ordered by standings criteria
    $teamStmt = $pdo->prepare("
        SELECT 
            t.team_id,
            t.team_name,
            p.pool_name,
            COALESCE(SUM(gr.win), 0) AS wins,
            COALESCE(SUM(gr.points_for), 0) - COALESCE(SUM(gr.points_against), 0) AS plus_minus,
            COALESCE(SUM(gr.points_for), 0) AS points_for
        FROM 
            teams t
        JOIN 
            team_pools tp ON t.team_id = tp.team_id
        JOIN 
            pools p ON tp.pool_id = p.pool_id
        JOIN 
            registrations r ON t.team_id = r.team_id
        LEFT JOIN 
            game_results gr ON t.team_id = gr.team_id
        WHERE 
            p.pool_id = :poolId AND r.year = :year AND r.status = 1
        GROUP BY 
            t.team_id, t.team_name, p.pool_name
        ORDER BY 
            wins DESC, plus_minus DESC, points_for DESC
    ");
    
    $teamStmt->execute([
        ':poolId' => $poolId,
        ':year' => $year
    ]);
    
    $teams = $teamStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return the team at the specified position, if available
    return isset($teams[$position]) ? $teams[$position] : null;
}

/**
 * Get HTML display for team with placeholder
 * 
 * @param PDO $pdo Database connection
 * @param string $placeholder Placeholder code
 * @param string|null $teamName Team name if already assigned
 * @param int $teamId Team ID if already assigned
 * @param int $year Tournament year
 * @return string Formatted HTML for displaying team
 */
function getTeamDisplayWithPlaceholder(PDO $pdo, string $placeholder, ?string $teamName, ?int $teamId, int $year = 2025): string {
    if (!empty($teamName)) {
        // If team is already assigned, show "Team Name (A1)"
        return htmlspecialchars($teamName) . ' <span class="placeholder-code">(' . htmlspecialchars($placeholder) . ')</span>';
    }
    
    // Handle "Winner of Game #X" placeholders
    if (preg_match('/^Winner of Game #(\d+)$/', $placeholder, $matches)) {
        $gameId = $matches[1];
        
        // Try to find the winner of the referenced game
        try {
            // First, get the teams playing in this game
            $gameStmt = $pdo->prepare("
                SELECT 
                    g.game_id,
                    g.home_team_id, g.away_team_id,
                    h.team_name AS home_team_name,
                    a.team_name AS away_team_name
                FROM 
                    schedule g
                LEFT JOIN 
                    teams h ON g.home_team_id = h.team_id
                LEFT JOIN 
                    teams a ON g.away_team_id = a.team_id
                WHERE 
                    g.game_id = :gameId
            ");
            $gameStmt->execute([':gameId' => $gameId]);
            $gameResult = $gameStmt->fetch(PDO::FETCH_ASSOC);
            
            // If we have a game, try to find the winner using win flag
            if ($gameResult) {
                // Look for a team with win=1 for this game
                $winnerStmt = $pdo->prepare("
                    SELECT 
                        gr.team_id,
                        t.team_name
                    FROM 
                        game_results gr
                    JOIN
                        teams t ON gr.team_id = t.team_id
                    WHERE 
                        gr.game_id = :gameId AND gr.win = 1
                    LIMIT 1
                ");
                $winnerStmt->execute([':gameId' => $gameId]);
                $winner = $winnerStmt->fetch(PDO::FETCH_ASSOC);
                
                // If we found a winner, return the team name
                if ($winner) {
                    return htmlspecialchars($winner['team_name']) . 
                           ' <span class="placeholder-code">(' . htmlspecialchars($placeholder) . ')</span>';
                }
                
                // If we can't determine a winner yet, display a message with the teams
                if (!empty($gameResult['home_team_name']) && !empty($gameResult['away_team_name'])) {
                    return '<span class="placeholder-unresolved">' . htmlspecialchars($placeholder) . 
                           ' (' . htmlspecialchars($gameResult['home_team_name']) . ' vs ' . 
                           htmlspecialchars($gameResult['away_team_name']) . ')</span>';
                }
            }
        } catch (PDOException $e) {
            // Log the error but continue
            error_log("Error resolving game winner: " . $e->getMessage());
        }
        
        // If we can't resolve the game or there was an error, just show the placeholder
        return '<span class="placeholder-unresolved">' . htmlspecialchars($placeholder) . '</span>';
    }
    
    // Handle standard pool position placeholders (A1, B2, etc.)
    // Try to resolve the placeholder
    $team = resolveTeamFromPlaceholder($pdo, $placeholder, $year);
    
    if ($team) {
        // Placeholder is resolved, show "Team Name (A1)"
        return htmlspecialchars($team['team_name']) . ' <span class="placeholder-code">(' . htmlspecialchars($placeholder) . ')</span>';
    } else {
        // Placeholder can't be resolved yet, just show the placeholder
        return '<span class="placeholder-unresolved">' . htmlspecialchars($placeholder) . '</span>';
    }
}

/**
 * Check if all pool games are complete for a given year
 * 
 * @param PDO $pdo Database connection
 * @param int $year Tournament year
 * @return bool True if all pool games have results
 */
function areAllPoolGamesComplete(PDO $pdo, int $year = 2025): bool {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS total_games, 
               COUNT(gr.game_id) AS games_with_results
        FROM schedule s
        LEFT JOIN game_results gr ON s.game_id = gr.game_id
        WHERE s.game_category = 'pool'
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return ($result['total_games'] > 0 && $result['total_games'] == $result['games_with_results']);
}

/**
 * Auto-update playoff games based on current standings
 * 
 * @param PDO $pdo Database connection
 * @param int $year Tournament year
 * @return int Number of games updated
 */
function autoUpdatePlayoffGames(PDO $pdo, int $year = 2025): int {
    $updateCount = 0;
    
    // Get all playoff games with placeholders
    $stmt = $pdo->prepare("
        SELECT 
            game_id, 
            placeholder_home, 
            placeholder_away
        FROM 
            schedule
        WHERE 
            game_category != 'pool' AND
            (placeholder_home IS NOT NULL OR placeholder_away IS NOT NULL)
    ");
    $stmt->execute();
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($games as $game) {
        $homeTeamId = null;
        $awayTeamId = null;
        
        // Resolve home team placeholder
        if (!empty($game['placeholder_home'])) {
            // Check if it's a "Winner of Game #X" placeholder
            if (preg_match('/^Winner of Game #(\d+)$/', $game['placeholder_home'], $matches)) {
                $previousGameId = $matches[1];
                
                // Get the winner of the previous game using win flag
                $winnerStmt = $pdo->prepare("
                    SELECT 
                        gr.team_id
                    FROM 
                        game_results gr
                    WHERE 
                        gr.game_id = :gameId AND gr.win = 1
                    LIMIT 1
                ");
                $winnerStmt->execute([':gameId' => $previousGameId]);
                $winner = $winnerStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($winner) {
                    $homeTeamId = $winner['team_id'];
                }
            } else {
                // Standard placeholder (A1, B2, etc.)
                $homeTeam = resolveTeamFromPlaceholder($pdo, $game['placeholder_home'], $year);
                if ($homeTeam) {
                    $homeTeamId = $homeTeam['team_id'];
                }
            }
        }
        
        // Resolve away team placeholder
        if (!empty($game['placeholder_away'])) {
            // Check if it's a "Winner of Game #X" placeholder
            if (preg_match('/^Winner of Game #(\d+)$/', $game['placeholder_away'], $matches)) {
                $previousGameId = $matches[1];
                
                // Get the winner of the previous game using win flag
                $winnerStmt = $pdo->prepare("
                    SELECT 
                        gr.team_id
                    FROM 
                        game_results gr
                    WHERE 
                        gr.game_id = :gameId AND gr.win = 1
                    LIMIT 1
                ");
                $winnerStmt->execute([':gameId' => $previousGameId]);
                $winner = $winnerStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($winner) {
                    $awayTeamId = $winner['team_id'];
                }
            } else {
                // Standard placeholder (A1, B2, etc.)
                $awayTeam = resolveTeamFromPlaceholder($pdo, $game['placeholder_away'], $year);
                if ($awayTeam) {
                    $awayTeamId = $awayTeam['team_id'];
                }
            }
        }
        
        // Update the game if we have both teams
        if ($homeTeamId && $awayTeamId) {
            $updateStmt = $pdo->prepare("
                UPDATE schedule
                SET home_team_id = :homeTeamId,
                    away_team_id = :awayTeamId
                WHERE game_id = :gameId
            ");
            
            $updateStmt->execute([
                ':homeTeamId' => $homeTeamId,
                ':awayTeamId' => $awayTeamId,
                ':gameId' => $game['game_id']
            ]);
            
            $updateCount++;
        }
    }
    
    return $updateCount;
} 
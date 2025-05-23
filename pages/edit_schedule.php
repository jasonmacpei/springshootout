<?php
// Include database and authentication
session_start();
require_once '/home/lostan6/springshootout.ca/includes/config.php';
require __DIR__ . '/../scripts/php/db_connect.php';
require_once __DIR__ . '/../scripts/php/resolve_placeholder.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: admin.php');
    exit;
}

// Variables to hold form data and errors
$formData = [
    'game_id' => '',
    'game_date' => '',
    'game_time' => '',
    'home_team_id' => '',
    'away_team_id' => '',
    'gym' => '',
    'game_type' => '',
    'placeholder_home' => '',
    'placeholder_away' => '',
    'game_category' => 'pool'
];
$errors = [];
$success = false;
$selectedGame = null;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle game update
    if (isset($_POST['update_game'])) {
        $formData['game_id'] = $_POST['game_id'] ?? '';
        $formData['game_date'] = $_POST['game_date'] ?? '';
        $formData['game_time'] = $_POST['game_time'] ?? '';
        $formData['home_team_id'] = $_POST['home_team_id'] ?? '';
        $formData['away_team_id'] = $_POST['away_team_id'] ?? '';
        $formData['gym'] = $_POST['gym'] ?? '';
        $formData['game_type'] = $_POST['game_type'] ?? '';
        $formData['placeholder_home'] = $_POST['placeholder_home'] ?? '';
        $formData['placeholder_away'] = $_POST['placeholder_away'] ?? '';
        $formData['game_category'] = $_POST['game_category'] ?? 'pool';
        
        // Basic validation
        if (empty($formData['game_date'])) {
            $errors[] = "Game date is required";
        }
        if (empty($formData['game_time'])) {
            $errors[] = "Game time is required";
        }
        if (empty($formData['gym'])) {
            $errors[] = "Gym is required";
        }
        if (empty($formData['game_type'])) {
            $errors[] = "Game type is required";
        }
        
        // If it's a non-pool game, validate placeholders
        if ($formData['game_category'] !== 'pool') {
            if (empty($formData['placeholder_home']) && empty($formData['home_team_id'])) {
                $errors[] = "Either Home Team or Home Placeholder is required";
            }
            if (empty($formData['placeholder_away']) && empty($formData['away_team_id'])) {
                $errors[] = "Either Away Team or Away Placeholder is required";
            }
        } else {
            // For pool games, team selection is required
            if (empty($formData['home_team_id'])) {
                $errors[] = "Home Team is required for pool games";
            }
            if (empty($formData['away_team_id'])) {
                $errors[] = "Away Team is required for pool games";
            }
            
            // Clear placeholders for pool games
            $formData['placeholder_home'] = null;
            $formData['placeholder_away'] = null;
        }
        
        // If no errors, update the game
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE schedule
                    SET game_date = :game_date,
                        game_time = :game_time,
                        home_team_id = :home_team_id,
                        away_team_id = :away_team_id,
                        gym = :gym,
                        game_type = :game_type,
                        placeholder_home = :placeholder_home,
                        placeholder_away = :placeholder_away,
                        game_category = :game_category
                    WHERE game_id = :game_id
                ");
                
                $params = [
                    ':game_id' => $formData['game_id'],
                    ':game_date' => $formData['game_date'],
                    ':game_time' => $formData['game_time'],
                    ':home_team_id' => $formData['home_team_id'] ?: null,
                    ':away_team_id' => $formData['away_team_id'] ?: null,
                    ':gym' => $formData['gym'],
                    ':game_type' => $formData['game_type'],
                    ':placeholder_home' => $formData['placeholder_home'] ?: null,
                    ':placeholder_away' => $formData['placeholder_away'] ?: null,
                    ':game_category' => $formData['game_category']
                ];
                
                $stmt->execute($params);
                $success = true;
                
                // Reset form data after successful update
                $formData = [
                    'game_id' => '',
                    'game_date' => '',
                    'game_time' => '',
                    'home_team_id' => '',
                    'away_team_id' => '',
                    'gym' => '',
                    'game_type' => '',
                    'placeholder_home' => '',
                    'placeholder_away' => '',
                    'game_category' => 'pool'
                ];
            } catch (PDOException $e) {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
    
    // Handle game deletion
    if (isset($_POST['delete_game'])) {
        $gameId = $_POST['game_id'] ?? '';
        
        if (!empty($gameId)) {
            try {
                // First check if this game has results
                $checkStmt = $pdo->prepare("
                    SELECT COUNT(*) AS result_count
                    FROM game_results
                    WHERE game_id = :game_id
                ");
                $checkStmt->execute([':game_id' => $gameId]);
                $resultCount = $checkStmt->fetch(PDO::FETCH_ASSOC)['result_count'];
                
                if ($resultCount > 0) {
                    $errors[] = "Cannot delete game with ID $gameId because it has results. Remove results first.";
                } else {
                    // Delete the game if no results exist
                    $deleteStmt = $pdo->prepare("
                        DELETE FROM schedule
                        WHERE game_id = :game_id
                    ");
                    $deleteStmt->execute([':game_id' => $gameId]);
                    $success = true;
                }
            } catch (PDOException $e) {
                $errors[] = "Database error: " . $e->getMessage();
            }
        } else {
            $errors[] = "Game ID is required for deletion";
        }
    }
    
    // Handle auto-update of playoff games based on standings
    if (isset($_POST['auto_update_playoffs'])) {
        try {
            // Call the enhanced auto-update function with debug enabled and pool check optional
            $debug = isset($_POST['run_diagnostics']) && $_POST['run_diagnostics'] == 1;
            $result = autoUpdatePlayoffGames($pdo, 2025, false, $debug);
            
            if ($result['update_count'] > 0) {
                $success = true;
                $_SESSION['message'] = "Successfully updated " . $result['update_count'] . " playoff game(s) based on current standings.";
            } else {
                // More detailed error message
                $errors[] = $result['message'];
                
                // Only add detailed diagnostics if not in full diagnostic mode
                if (!$debug) {
                    // Add detailed diagnostics for unresolved placeholders
                    if (!empty($result['diagnostics'])) {
                        $unresolved = [];
                        foreach ($result['diagnostics'] as $game) {
                            if (!$game['updated']) {
                                $gameInfo = "Game #" . $game['game_id'] . " (" . $game['game_type'] . " " . $game['game_category'] . ")";
                                
                                if (!$game['home']['resolved']) {
                                    $errorReason = $game['home']['reason'] ?? "Could not resolve placeholder";
                                    // Add specific reason if available
                                    if (!empty($game['home']['error_details'])) {
                                        $errorStep = $game['home']['error_details']['step'] ?? '';
                                        if ($errorStep === 'pool_lookup') {
                                            $errorReason = "Pool not found: " . $game['home']['error_details']['details']['pool_letter'] ?? '';
                                        } else if ($errorStep === 'position_lookup') {
                                            $position = $game['home']['error_details']['details']['position'] ?? '?';
                                            $available = $game['home']['error_details']['details']['available_positions'] ?? 0;
                                            $errorReason = "Position $position not available (only $available position(s) in pool)";
                                        } else if ($errorStep === 'team_lookup') {
                                            $errorReason = "No teams found in pool";
                                        } else if ($errorStep === 'parsing') {
                                            $errorReason = "Could not parse placeholder format";
                                        }
                                    }
                                    
                                    $unresolved[] = $gameInfo . ": Could not resolve home placeholder \"" . 
                                        $game['home']['placeholder'] . "\" - " . $errorReason;
                                }
                                
                                if (!$game['away']['resolved']) {
                                    $errorReason = $game['away']['reason'] ?? "Could not resolve placeholder";
                                    // Add specific reason if available
                                    if (!empty($game['away']['error_details'])) {
                                        $errorStep = $game['away']['error_details']['step'] ?? '';
                                        if ($errorStep === 'pool_lookup') {
                                            $errorReason = "Pool not found: " . $game['away']['error_details']['details']['pool_letter'] ?? '';
                                        } else if ($errorStep === 'position_lookup') {
                                            $position = $game['away']['error_details']['details']['position'] ?? '?';
                                            $available = $game['away']['error_details']['details']['available_positions'] ?? 0;
                                            $errorReason = "Position $position not available (only $available position(s) in pool)";
                                        } else if ($errorStep === 'team_lookup') {
                                            $errorReason = "No teams found in pool";
                                        } else if ($errorStep === 'parsing') {
                                            $errorReason = "Could not parse placeholder format";
                                        }
                                    }
                                    
                                    $unresolved[] = $gameInfo . ": Could not resolve away placeholder \"" . 
                                        $game['away']['placeholder'] . "\" - " . $errorReason;
                                }
                            }
                        }
                        
                        // Add the first 5 unresolved placeholders to the errors
                        $maxToShow = 5;
                        $count = min(count($unresolved), $maxToShow);
                        for ($i = 0; $i < $count; $i++) {
                            $errors[] = $unresolved[$i];
                        }
                        
                        // If there are more than 5, add a summary message
                        if (count($unresolved) > $maxToShow) {
                            $errors[] = "...and " . (count($unresolved) - $maxToShow) . " more unresolved placeholders.";
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $errors[] = "Error updating playoff games: " . $e->getMessage();
        }
    }
}

// Handle edit request
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $gameId = $_GET['edit'];
    
    try {
        $stmt = $pdo->prepare("
            SELECT s.*, h.team_name AS home_team_name, a.team_name AS away_team_name
            FROM schedule s
            LEFT JOIN teams h ON s.home_team_id = h.team_id
            LEFT JOIN teams a ON s.away_team_id = a.team_id
            WHERE s.game_id = :game_id
        ");
        $stmt->execute([':game_id' => $gameId]);
        $selectedGame = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($selectedGame) {
            $formData = [
                'game_id' => $selectedGame['game_id'],
                'game_date' => $selectedGame['game_date'],
                'game_time' => $selectedGame['game_time'],
                'home_team_id' => $selectedGame['home_team_id'],
                'away_team_id' => $selectedGame['away_team_id'],
                'gym' => $selectedGame['gym'],
                'game_type' => $selectedGame['game_type'],
                'placeholder_home' => $selectedGame['placeholder_home'],
                'placeholder_away' => $selectedGame['placeholder_away'],
                'game_category' => $selectedGame['game_category']
            ];
        }
    } catch (PDOException $e) {
        $errors[] = "Database error: " . $e->getMessage();
    }
}

// Get all schedule entries
try {
    $scheduleSql = "
        SELECT s.game_id, s.game_date, s.game_time, h.team_name AS home_team_name, 
               a.team_name AS away_team_name, s.gym, s.game_type, s.game_category,
               s.placeholder_home, s.placeholder_away
        FROM schedule s
        LEFT JOIN teams h ON s.home_team_id = h.team_id
        LEFT JOIN teams a ON s.away_team_id = a.team_id
        ORDER BY s.game_date, s.game_time
    ";
    $scheduleStmt = $pdo->query($scheduleSql);
    $scheduleRows = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Error fetching schedule: " . $e->getMessage();
    $scheduleRows = [];
}

// Get all teams for dropdown
try {
    $teamsSql = "
        SELECT t.team_id, t.team_name, r.division
        FROM teams t
        JOIN registrations r ON t.team_id = r.team_id
        WHERE r.year = 2025 AND r.status = 1
        ORDER BY r.division, t.team_name
    ";
    $teamsStmt = $pdo->query($teamsSql);
    $teams = $teamsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Error fetching teams: " . $e->getMessage();
    $teams = [];
}

// Get all pools for placeholder options
try {
    $poolsSql = "
        SELECT p.pool_id, p.pool_name, COUNT(tp.team_id) AS team_count
        FROM pools p
        JOIN team_pools tp ON p.pool_id = tp.pool_id
        GROUP BY p.pool_id, p.pool_name
        ORDER BY p.pool_name
    ";
    $poolsStmt = $pdo->query($poolsSql);
    $pools = $poolsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate placeholder options (A1, A2, B1, B2, etc.)
    $placeholderOptions = [];
    foreach ($pools as $pool) {
        $poolLetter = $pool['pool_name'];
        for ($i = 1; $i <= $pool['team_count']; $i++) {
            $placeholderOptions[] = $poolLetter . $i;
        }
    }
} catch (PDOException $e) {
    $errors[] = "Error fetching pools: " . $e->getMessage();
    $pools = [];
    $placeholderOptions = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Schedule</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background-color: black;
            color: white;
        }
        .container {
            padding-top: 20px;
        }
        .table {
            color: white;
        }
        .form-group label {
            color: white;
        }
        .placeholder-note {
            font-size: 0.85em;
            color: #aaa;
            margin-top: 5px;
        }
        .game-category-info {
            display: none;
            padding: 10px;
            margin-top: 10px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
        }
        .game-crossover, .game-quarterfinal, .game-semifinal, .game-final {
            font-weight: bold;
            padding: 3px 6px;
            border-radius: 3px;
        }
        .game-crossover {
            background-color: rgba(255, 170, 0, 0.2);
        }
        .game-quarterfinal {
            background-color: rgba(0, 170, 255, 0.2);
        }
        .game-semifinal {
            background-color: rgba(170, 0, 255, 0.2);
        }
        .game-final {
            background-color: rgba(255, 0, 0, 0.2);
        }
        .auto-update-container {
            margin-top: 20px;
            padding: 15px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
        }
        
        /* Diagnostic Display Improvements */
        .error-details-btn, .pool-details-btn {
            opacity: 0.8;
            transition: all 0.2s ease;
        }
        .error-details-btn:hover, .pool-details-btn:hover {
            opacity: 1;
            transform: translateY(-2px);
        }
        .alert-warning {
            color: #000 !important;
            background-color: #ffeca8 !important;
            border-color: #ffe38f !important;
        }
        code {
            background-color: rgba(0,0,0,0.2);
            padding: 2px 4px;
            border-radius: 3px;
            font-family: monospace;
            color: #ff9900;
        }
        .table-dark {
            background-color: #1e1e1e;
        }
        .table-dark thead th {
            background-color: #333;
            border-bottom: 2px solid #ff6b00;
        }
        .card-header {
            background-color: #2a2a2a;
            border-bottom: 1px solid #444;
        }
        .alert-info {
            background-color: #004d7a !important;
            border-color: #002d47 !important;
            color: #ffffff !important;
        }
        pre {
            background-color: #111;
            padding: 8px;
            border-radius: 4px;
            color: #ddd;
            font-size: 0.85em;
            overflow-x: auto;
            max-height: 300px;
            margin-top: 5px;
        }
    </style>
</head>
<body>

<!-- NavBar -->
<div class="container">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark justify-content-center">
        <div id="nav-placeholder"></div>
    </nav>
</div>

<!-- Poster Logo Header -->
<div class="poster">
    <img src="../assets/images/name.png" alt="Spring Shootout">
</div>

<div class="container">
    <h1 class="mt-4">Edit Schedule</h1>
    
    <!-- Display errors -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <!-- Display success message -->
    <?php if ($success): ?>
        <div class="alert alert-success">
            Schedule updated successfully!
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success">
            <?php 
            echo htmlspecialchars($_SESSION['message']);
            unset($_SESSION['message']);
            ?>
        </div>
    <?php endif; ?>
    
    <!-- Auto-update playoff games option -->
    <div class="auto-update-container">
        <h4>Auto-Update Playoff Games</h4>
        <p>This will automatically assign teams to playoff games based on current standings.</p>
        
        <?php 
        // Check if all pool games have results
        $allPoolGamesComplete = areAllPoolGamesComplete($pdo);
        if (!$allPoolGamesComplete): 
        ?>
            <div class="alert alert-warning">
                <strong>Note:</strong> Not all pool games have results yet. The auto-update may not be able to resolve all placeholders.
            </div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="row mb-3">
                <div class="col-md-12">
                    <button type="submit" name="auto_update_playoffs" class="btn btn-warning" 
                            onclick="return confirm('Are you sure you want to auto-update all playoff games based on current standings?')">
                        Auto-Update Playoff Games
                    </button>
                    
                    <a href="#" class="btn btn-info ml-2" data-toggle="collapse" data-target="#placeholderHelp">
                        Placeholder Format Help
                    </a>
                    
                    <div class="form-check form-check-inline ml-3">
                        <input class="form-check-input" type="checkbox" name="run_diagnostics" id="run_diagnostics" value="1">
                        <label class="form-check-label text-white" for="run_diagnostics">Run Full Diagnostics</label>
                    </div>
                </div>
            </div>
            
            <div id="placeholderHelp" class="collapse mt-3">
                <div class="card bg-dark">
                    <div class="card-header">
                        <h5>Supported Placeholder Formats</h5>
                    </div>
                    <div class="card-body">
                        <p>The auto-update system supports these placeholder formats:</p>
                        <div class="table-responsive">
                            <table class="table table-sm table-dark">
                                <thead>
                                    <tr>
                                        <th>Format</th>
                                        <th>Example</th>
                                        <th>Meaning</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Simple format</td>
                                        <td><code>A1</code>, <code>B2</code>, <code>C3</code></td>
                                        <td>Pool letter + position</td>
                                    </tr>
                                    <tr>
                                        <td>Division format</td>
                                        <td><code>u11 Pool A1</code>, <code>u12 Pool B2</code></td>
                                        <td>Division + pool + position</td>
                                    </tr>
                                    <tr>
                                        <td>Game winners</td>
                                        <td><code>Winner of Game #30</code></td>
                                        <td>References previous game winners</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <h6>For best results when creating playoff games:</h6>
                            <ol>
                                <li>Use the formats above exactly as shown</li>
                                <li>Include the division (u11, u12, etc.) in the placeholder for division-specific games</li>
                                <li>Ensure pool games have complete results before using the auto-update feature</li>
                                <li>Verify that the pool letter in your placeholder matches a pool name in the database</li>
                            </ol>
                        </div>
                        
                        <h6 class="mt-3">Important Notes About Pool Names:</h6>
                        <p>The system tries to match pool references in several ways:</p>
                        <ol>
                            <li>Exact match (e.g., "A" in database = "A" in placeholder)</li>
                            <li>Normalized match (e.g., "Pool A" in database = "A" in placeholder)</li>
                            <li>Pattern match (e.g., "u11 A" in database = "A" in placeholder)</li>
                        </ol>
                        <p>For most reliable results, ensure your pool names in the database are simple (like "A", "B", "C") or include the division prefix consistently (like "u11 A", "u11 B", etc.).</p>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <!-- This is where we'll show diagnostic information if requested -->
    <?php 
    if (isset($_POST['auto_update_playoffs']) && isset($_POST['run_diagnostics']) && !empty($result) && empty($result['update_count'])): 
    ?>
    <div class="card mt-4 bg-dark">
        <div class="card-header">
            <h5>Placeholder Resolution Diagnostics</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <strong>Diagnostic Mode:</strong> Showing detailed information to help resolve placeholder issues.
            </div>
            
            <h6 class="mt-4 mb-3">Available Pools in Database</h6>
            <div class="table-responsive">
                <table class="table table-sm table-dark">
                    <thead>
                        <tr>
                            <th>Pool ID</th>
                            <th>Pool Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($result['available_pools'])): ?>
                            <?php foreach ($result['available_pools'] as $pool): ?>
                                <tr>
                                    <td><?php echo $pool['pool_id']; ?></td>
                                    <td><?php echo htmlspecialchars($pool['pool_name']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2">No pools found in database</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <h6 class="mt-4 mb-3">Pool Name Mapping</h6>
            <p>The system will attempt to match placeholders to pools using these variations:</p>
            <div class="table-responsive">
                <table class="table table-sm table-dark">
                    <thead>
                        <tr>
                            <th>Database Pool Name</th>
                            <th>Will Match Placeholders</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($result['pool_mapping_info'])): ?>
                            <?php foreach ($result['pool_mapping_info'] as $poolName => $mappingInfo): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($poolName); ?></td>
                                    <td>
                                        <?php 
                                        if (!empty($mappingInfo['will_match_for'])) {
                                            echo '<code>' . htmlspecialchars(implode('</code>, <code>', array_unique($mappingInfo['will_match_for']))) . '</code>';
                                        } else {
                                            echo 'No mapping information';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2">No pool mapping information available</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <h6 class="mt-4 mb-3">Pools Analysis</h6>
            <?php if (!empty($result['pool_analysis'])): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-sm table-dark">
                        <thead>
                            <tr>
                                <th>Pool</th>
                                <th>Teams</th>
                                <th>Has Standings</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($result['pool_analysis'] as $poolName => $poolData): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($poolName); ?></td>
                                    <td><?php echo $poolData['team_count']; ?></td>
                                    <td><?php echo $poolData['has_standings'] ? 'Yes' : 'No'; ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info pool-details-btn" type="button" data-toggle="collapse" 
                                                data-target="#pool-<?php echo $poolData['pool_id']; ?>">
                                            View Details
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="p-0">
                                        <div class="collapse" id="pool-<?php echo $poolData['pool_id']; ?>">
                                            <div class="p-3">
                                                <h6>Teams in Pool:</h6>
                                                <ul>
                                                    <?php foreach ($poolData['team_list'] as $team): ?>
                                                        <li><?php echo htmlspecialchars($team['team_name'] . ' (' . $team['division'] . ')'); ?> 
                                                            - Status: <?php echo $team['status'] == 1 ? 'Active' : 'Inactive'; ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                                
                                                <h6>Standings:</h6>
                                                <?php if (empty($poolData['standings'])): ?>
                                                    <p>No standings available for this pool.</p>
                                                <?php else: ?>
                                                    <table class="table table-sm table-dark">
                                                        <thead>
                                                            <tr>
                                                                <th>Position</th>
                                                                <th>Team</th>
                                                                <th>Division</th>
                                                                <th>Wins</th>
                                                                <th>+/-</th>
                                                                <th>PF</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($poolData['standings'] as $index => $team): ?>
                                                                <tr>
                                                                    <td><?php echo $index + 1; ?></td>
                                                                    <td><?php echo htmlspecialchars($team['team_name']); ?></td>
                                                                    <td><?php echo htmlspecialchars($team['division']); ?></td>
                                                                    <td><?php echo $team['wins']; ?></td>
                                                                    <td><?php echo $team['plus_minus']; ?></td>
                                                                    <td><?php echo $team['points_for']; ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No pool analysis available.</p>
            <?php endif; ?>
            
            <h6 class="mt-4 mb-3">Failed Placeholders</h6>
            <?php if (!empty($result['diagnostics'])): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-sm table-dark">
                        <thead>
                            <tr>
                                <th>Game #</th>
                                <th>Type</th>
                                <th>Category</th>
                                <th>Home Placeholder</th>
                                <th>Away Placeholder</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($result['diagnostics'] as $game): ?>
                                <?php if (!$game['updated']): ?>
                                    <tr>
                                        <td><?php echo $game['game_id']; ?></td>
                                        <td><?php echo htmlspecialchars($game['game_type']); ?></td>
                                        <td><?php echo htmlspecialchars($game['game_category']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($game['home']['placeholder']); ?>
                                            <?php if (!$game['home']['resolved']): ?>
                                                <span class="text-danger">
                                                    <br>Error: <?php echo $game['home']['reason']; ?>
                                                    <?php if (!empty($game['home']['error_details'])): ?>
                                                        <button class="btn btn-sm btn-outline-danger ml-2 error-details-btn" type="button" data-toggle="collapse" 
                                                                data-target="#home-error-<?php echo $game['game_id']; ?>">
                                                            Details
                                                        </button>
                                                        <div class="collapse mt-2" id="home-error-<?php echo $game['game_id']; ?>">
                                                            <div class="p-2 border border-danger rounded">
                                                                <p><strong>Step:</strong> <?php echo $game['home']['error_details']['step']; ?></p>
                                                                <p><strong>Message:</strong> <?php echo $game['home']['error_details']['message']; ?></p>
                                                                <?php if (!empty($game['home']['error_details']['details'])): ?>
                                                                    <p><strong>Details:</strong></p>
                                                                    <ul>
                                                                        <?php foreach ($game['home']['error_details']['details'] as $key => $value): ?>
                                                                            <li><strong><?php echo htmlspecialchars(ucfirst($key)); ?>:</strong> 
                                                                                <?php 
                                                                                if (is_array($value)) {
                                                                                    if (!empty($value)) {
                                                                                        echo '<pre>' . htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT)) . '</pre>';
                                                                                    } else {
                                                                                        echo 'Empty array';
                                                                                    }
                                                                                } else {
                                                                                    echo htmlspecialchars($value);
                                                                                } 
                                                                                ?>
                                                                            </li>
                                                                        <?php endforeach; ?>
                                                                    </ul>
                                                                    
                                                                    <?php if ($game['home']['error_details']['step'] === 'pool_lookup' && !empty($game['home']['error_details']['details']['attempted_matches'])): ?>
                                                                        <p><strong>Attempted Pool Matches:</strong></p>
                                                                        <ul>
                                                                            <li>Exact match: "<?php echo htmlspecialchars($game['home']['error_details']['details']['attempted_matches']['exact']); ?>"</li>
                                                                            <li>Normalized match: "<?php echo htmlspecialchars($game['home']['error_details']['details']['attempted_matches']['normalized']); ?>"</li>
                                                                            <li>Pattern matches: 
                                                                                <?php 
                                                                                foreach ($game['home']['error_details']['details']['attempted_matches']['patterns'] as $pattern) {
                                                                                    echo '"' . htmlspecialchars($pattern) . '", ';
                                                                                }
                                                                                ?>
                                                                            </li>
                                                                            <?php if (!empty($game['home']['error_details']['details']['attempted_matches']['division_formats'])): ?>
                                                                                <li>Division formats: 
                                                                                    <?php 
                                                                                    foreach ($game['home']['error_details']['details']['attempted_matches']['division_formats'] as $format) {
                                                                                        echo '"' . htmlspecialchars($format) . '", ';
                                                                                    }
                                                                                    ?>
                                                                                </li>
                                                                            <?php endif; ?>
                                                                        </ul>
                                                                    <?php endif; ?>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($game['away']['placeholder']); ?>
                                            <?php if (!$game['away']['resolved']): ?>
                                                <span class="text-danger">
                                                    <br>Error: <?php echo $game['away']['reason']; ?>
                                                    <?php if (!empty($game['away']['error_details'])): ?>
                                                        <button class="btn btn-sm btn-outline-danger ml-2 error-details-btn" type="button" data-toggle="collapse" 
                                                                data-target="#away-error-<?php echo $game['game_id']; ?>">
                                                            Details
                                                        </button>
                                                        <div class="collapse mt-2" id="away-error-<?php echo $game['game_id']; ?>">
                                                            <div class="p-2 border border-danger rounded">
                                                                <p><strong>Step:</strong> <?php echo $game['away']['error_details']['step']; ?></p>
                                                                <p><strong>Message:</strong> <?php echo $game['away']['error_details']['message']; ?></p>
                                                                <?php if (!empty($game['away']['error_details']['details'])): ?>
                                                                    <p><strong>Details:</strong></p>
                                                                    <ul>
                                                                        <?php foreach ($game['away']['error_details']['details'] as $key => $value): ?>
                                                                            <li><strong><?php echo htmlspecialchars(ucfirst($key)); ?>:</strong> 
                                                                                <?php 
                                                                                if (is_array($value)) {
                                                                                    if (!empty($value)) {
                                                                                        echo '<pre>' . htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT)) . '</pre>';
                                                                                    } else {
                                                                                        echo 'Empty array';
                                                                                    }
                                                                                } else {
                                                                                    echo htmlspecialchars($value);
                                                                                } 
                                                                                ?>
                                                                            </li>
                                                                        <?php endforeach; ?>
                                                                    </ul>
                                                                    
                                                                    <?php if ($game['away']['error_details']['step'] === 'pool_lookup' && !empty($game['away']['error_details']['details']['attempted_matches'])): ?>
                                                                        <p><strong>Attempted Pool Matches:</strong></p>
                                                                        <ul>
                                                                            <li>Exact match: "<?php echo htmlspecialchars($game['away']['error_details']['details']['attempted_matches']['exact']); ?>"</li>
                                                                            <li>Normalized match: "<?php echo htmlspecialchars($game['away']['error_details']['details']['attempted_matches']['normalized']); ?>"</li>
                                                                            <li>Pattern matches: 
                                                                                <?php 
                                                                                foreach ($game['away']['error_details']['details']['attempted_matches']['patterns'] as $pattern) {
                                                                                    echo '"' . htmlspecialchars($pattern) . '", ';
                                                                                }
                                                                                ?>
                                                                            </li>
                                                                            <?php if (!empty($game['away']['error_details']['details']['attempted_matches']['division_formats'])): ?>
                                                                                <li>Division formats: 
                                                                                    <?php 
                                                                                    foreach ($game['away']['error_details']['details']['attempted_matches']['division_formats'] as $format) {
                                                                                        echo '"' . htmlspecialchars($format) . '", ';
                                                                                    }
                                                                                    ?>
                                                                                </li>
                                                                            <?php endif; ?>
                                                                        </ul>
                                                                    <?php endif; ?>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="text-danger">Failed</span></td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No failed placeholders detected.</p>
            <?php endif; ?>
            
            <div class="alert alert-warning mt-3" style="color: #000; background-color: #ffeca8; border-color: #ffe38f;">
                <strong>Possible Solutions:</strong>
                <ol>
                    <li>Make sure all pool games have results entered</li>
                    <li>Check that teams are properly assigned to pools</li>
                    <li>Verify that pool names in placeholders match pool names in the database (case-sensitive)</li>
                    <li>For division-specific games, ensure the division in the placeholder matches the team's division</li>
                    <li>For game winner placeholders, check that the referenced game has a winner recorded</li>
                </ol>
                <p class="mt-3 mb-0"><strong>Common Placeholder Format Examples:</strong></p>
                <ul>
                    <li><code>A1</code> - 1st place in Pool A</li>
                    <li><code>B2</code> - 2nd place in Pool B</li>
                    <li><code>u11 Pool A1</code> - 1st place in u11 Division Pool A</li>
                    <li><code>u12 Pool B2</code> - 2nd place in u12 Division Pool B</li>
                    <li><code>Winner of Game #30</code> - Winner of Game 30</li>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Edit form -->
    <?php if ($selectedGame): ?>
        <div class="card bg-dark mt-4">
            <div class="card-header">
                <h3>Edit Game</h3>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <input type="hidden" name="game_id" value="<?php echo htmlspecialchars($formData['game_id']); ?>">
                    
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="game_date">Game Date</label>
                            <input type="date" class="form-control" id="game_date" name="game_date" 
                                  value="<?php echo htmlspecialchars($formData['game_date']); ?>" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="game_time">Game Time</label>
                            <input type="time" class="form-control" id="game_time" name="game_time" 
                                  value="<?php echo htmlspecialchars($formData['game_time']); ?>" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="gym">Gym</label>
                            <select class="form-control" id="gym" name="gym" required>
                                <option value="">Select Gym</option>
                                <option value="Town Hall" <?php echo $formData['gym'] === 'Town Hall' ? 'selected' : ''; ?>>Town Hall</option>
                                <option value="Donagh" <?php echo $formData['gym'] === 'Donagh' ? 'selected' : ''; ?>>Donagh</option>
                                <option value="Stonepark" <?php echo $formData['gym'] === 'Stonepark' ? 'selected' : ''; ?>>Stonepark</option>
                                <option value="Rural" <?php echo $formData['gym'] === 'Rural' ? 'selected' : ''; ?>>Rural</option>
                                <option value="Glen Stewart" <?php echo $formData['gym'] === 'Glen Stewart' ? 'selected' : ''; ?>>Glen Stewart</option>
                                <option value="Colonel Grey" <?php echo $formData['gym'] === 'Colonel Grey' ? 'selected' : ''; ?>>Colonel Grey</option>
                                <option value="UPEI" <?php echo $formData['gym'] === 'UPEI' ? 'selected' : ''; ?>>UPEI</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="game_type">Game Type</label>
                            <input type="text" class="form-control" id="game_type" name="game_type" 
                                  value="<?php echo htmlspecialchars($formData['game_type']); ?>" required>
                            <small class="form-text text-muted">e.g., u11, u12, u13</small>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="game_category">Game Category</label>
                            <select class="form-control" id="game_category" name="game_category" onchange="togglePlaceholderFields()">
                                <option value="pool" <?php echo $formData['game_category'] === 'pool' ? 'selected' : ''; ?>>Pool Play</option>
                                <option value="crossover" <?php echo $formData['game_category'] === 'crossover' ? 'selected' : ''; ?>>Crossover</option>
                                <option value="quarterfinal" <?php echo $formData['game_category'] === 'quarterfinal' ? 'selected' : ''; ?>>Quarterfinal</option>
                                <option value="semifinal" <?php echo $formData['game_category'] === 'semifinal' ? 'selected' : ''; ?>>Semifinal</option>
                                <option value="final" <?php echo $formData['game_category'] === 'final' ? 'selected' : ''; ?>>Final</option>
                            </select>
                        </div>
                    </div>
                    
                    <div id="game-category-info" class="game-category-info">
                        <p>For playoff games, you can use either:</p>
                        <ul>
                            <li>Specific teams (if already known)</li>
                            <li>Placeholders (like "A1" for "1st place in Pool A")</li>
                        </ul>
                        <p>Placeholders will be automatically resolved once pool play is complete.</p>
                    </div>
                    
                    <!-- Team selection or placeholder -->
                    <div class="form-row">
                        <div class="form-group col-md-5">
                            <label for="home_team_id">Home Team</label>
                            <select class="form-control" id="home_team_id" name="home_team_id">
                                <option value="">Select Home Team</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?php echo $team['team_id']; ?>" 
                                            <?php echo $formData['home_team_id'] == $team['team_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($team['division'] . ' - ' . $team['team_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group col-md-2 text-center" style="padding-top: 35px;">
                            <strong>OR</strong>
                        </div>
                        
                        <div class="form-group col-md-5 placeholder-field" <?php echo $formData['game_category'] === 'pool' ? 'style="display: none;"' : ''; ?>>
                            <label for="placeholder_home">Home Placeholder</label>
                            <select class="form-control" id="placeholder_home" name="placeholder_home">
                                <option value="">Select Placeholder</option>
                                <?php foreach ($placeholderOptions as $option): ?>
                                    <option value="<?php echo $option; ?>" 
                                            <?php echo $formData['placeholder_home'] === $option ? 'selected' : ''; ?>>
                                        <?php echo $option; ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="Winner of Game #" <?php echo strpos($formData['placeholder_home'], 'Winner of Game #') === 0 ? 'selected' : ''; ?>>
                                    Winner of a Previous Game
                                </option>
                                <option value="custom" <?php echo !empty($formData['placeholder_home']) && 
                                    !in_array($formData['placeholder_home'], $placeholderOptions) && 
                                    strpos($formData['placeholder_home'], 'Winner of Game #') !== 0 ? 'selected' : ''; ?>>
                                    Custom Text
                                </option>
                            </select>
                            <div class="placeholder-note">Example: A1 = 1st place in Pool A</div>
                            
                            <!-- Custom game ID input (only shown if "Winner of Game #" is selected) -->
                            <div id="home-game-id-container" class="mt-2" style="display: none;">
                                <label for="home_game_id">Game ID</label>
                                <input type="text" class="form-control" id="home_game_id" placeholder="Enter Game ID"
                                       value="<?php echo strpos($formData['placeholder_home'], 'Winner of Game #') === 0 ? 
                                             substr($formData['placeholder_home'], strlen('Winner of Game #')) : ''; ?>">
                                <div class="placeholder-note">
                                    This will create "Winner of Game #[ID]"
                                </div>
                            </div>
                            
                            <!-- Custom text input (only shown if "Custom Text" is selected) -->
                            <div id="home-custom-container" class="mt-2" style="display: none;">
                                <label for="home_custom_text">Custom Placeholder Text</label>
                                <input type="text" class="form-control" id="home_custom_text" 
                                       placeholder="e.g., A1 vs B2" 
                                       value="<?php echo (!empty($formData['placeholder_home']) && 
                                           !in_array($formData['placeholder_home'], $placeholderOptions) && 
                                           strpos($formData['placeholder_home'], 'Winner of Game #') !== 0) ? 
                                           htmlspecialchars($formData['placeholder_home']) : ''; ?>">
                                <div class="placeholder-note">
                                    Enter any text that describes this placeholder (e.g., "A1 vs B2", "Pool A Winner")
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group col-md-5">
                            <label for="away_team_id">Away Team</label>
                            <select class="form-control" id="away_team_id" name="away_team_id">
                                <option value="">Select Away Team</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?php echo $team['team_id']; ?>" 
                                            <?php echo $formData['away_team_id'] == $team['team_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($team['division'] . ' - ' . $team['team_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group col-md-2 text-center" style="padding-top: 35px;">
                            <strong>OR</strong>
                        </div>
                        
                        <div class="form-group col-md-5 placeholder-field" <?php echo $formData['game_category'] === 'pool' ? 'style="display: none;"' : ''; ?>>
                            <label for="placeholder_away">Away Placeholder</label>
                            <select class="form-control" id="placeholder_away" name="placeholder_away">
                                <option value="">Select Placeholder</option>
                                <?php foreach ($placeholderOptions as $option): ?>
                                    <option value="<?php echo $option; ?>" 
                                            <?php echo $formData['placeholder_away'] === $option ? 'selected' : ''; ?>>
                                        <?php echo $option; ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="Winner of Game #" <?php echo strpos($formData['placeholder_away'], 'Winner of Game #') === 0 ? 'selected' : ''; ?>>
                                    Winner of a Previous Game
                                </option>
                                <option value="custom" <?php echo !empty($formData['placeholder_away']) && 
                                    !in_array($formData['placeholder_away'], $placeholderOptions) && 
                                    strpos($formData['placeholder_away'], 'Winner of Game #') !== 0 ? 'selected' : ''; ?>>
                                    Custom Text
                                </option>
                            </select>
                            <div class="placeholder-note">Example: B2 = 2nd place in Pool B</div>
                            
                            <!-- Custom game ID input (only shown if "Winner of Game #" is selected) -->
                            <div id="away-game-id-container" class="mt-2" style="display: none;">
                                <label for="away_game_id">Game ID</label>
                                <input type="text" class="form-control" id="away_game_id" placeholder="Enter Game ID"
                                       value="<?php echo strpos($formData['placeholder_away'], 'Winner of Game #') === 0 ? 
                                             substr($formData['placeholder_away'], strlen('Winner of Game #')) : ''; ?>">
                                <div class="placeholder-note">
                                    This will create "Winner of Game #[ID]"
                                </div>
                            </div>
                            
                            <!-- Custom text input (only shown if "Custom Text" is selected) -->
                            <div id="away-custom-container" class="mt-2" style="display: none;">
                                <label for="away_custom_text">Custom Placeholder Text</label>
                                <input type="text" class="form-control" id="away_custom_text" 
                                       placeholder="e.g., C1 vs D2" 
                                       value="<?php echo (!empty($formData['placeholder_away']) && 
                                           !in_array($formData['placeholder_away'], $placeholderOptions) && 
                                           strpos($formData['placeholder_away'], 'Winner of Game #') !== 0) ? 
                                           htmlspecialchars($formData['placeholder_away']) : ''; ?>">
                                <div class="placeholder-note">
                                    Enter any text that describes this placeholder (e.g., "C1 vs D2", "Pool B Runner-up")
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row mt-4">
                        <div class="col-md-12">
                            <button type="submit" name="update_game" class="btn btn-primary">Update Game</button>
                            <button type="submit" name="delete_game" class="btn btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this game?')">
                                Delete Game
                            </button>
                            <a href="edit_schedule.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Schedule table -->
    <h3 class="mt-4">Schedule</h3>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Home Team</th>
                    <th>Away Team</th>
                    <th>Gym</th>
                    <th>Type</th>
                    <th>Category</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($scheduleRows as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['game_date']); ?></td>
                        <td>
                            <?php 
                            $time = new DateTime($row['game_time']);
                            echo $time->format('g:i a'); 
                            ?>
                        </td>
                        <td>
                            <?php 
                            if (!empty($row['placeholder_home'])) {
                                echo htmlspecialchars($row['placeholder_home']);
                                if (!empty($row['home_team_name'])) {
                                    echo ' (' . htmlspecialchars($row['home_team_name']) . ')';
                                }
                            } else {
                                echo $row['home_team_name'] ? htmlspecialchars($row['home_team_name']) : 'TBD';
                            }
                            ?>
                        </td>
                        <td>
                            <?php 
                            if (!empty($row['placeholder_away'])) {
                                echo htmlspecialchars($row['placeholder_away']);
                                if (!empty($row['away_team_name'])) {
                                    echo ' (' . htmlspecialchars($row['away_team_name']) . ')';
                                }
                            } else {
                                echo $row['away_team_name'] ? htmlspecialchars($row['away_team_name']) : 'TBD';
                            }
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['gym']); ?></td>
                        <td><?php echo htmlspecialchars($row['game_type']); ?></td>
                        <td>
                            <span class="game-<?php echo strtolower($row['game_category']); ?>">
                                <?php echo htmlspecialchars(ucfirst($row['game_category'])); ?>
                            </span>
                        </td>
                        <td>
                            <a href="edit_schedule.php?edit=<?php echo $row['game_id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                
                <?php if (empty($scheduleRows)): ?>
                    <tr>
                        <td colspan="8" class="text-center">No schedule entries found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="my-4">
        <a href="menu.php" class="btn btn-secondary">Back to Menu</a>
        <a href="schedule.php" class="btn btn-info">View Schedule</a>
    </div>
</div>

<!-- jQuery and Bootstrap JS -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<!-- Diagnostics JS for collapsible details -->
<script>
    $(document).ready(function() {
        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();
        
        // Fix for Bootstrap 5 collapse functionality with data-toggle attributes
        $('[data-toggle="collapse"]').on('click', function() {
            var target = $(this).data('target');
            $(target).collapse('toggle');
            return false;
        });
        
        // Special handling for error details buttons to ensure they work
        $('.error-details-btn').on('click', function() {
            var target = $(this).data('target');
            console.log("Toggling error details:", target);
            $(target).collapse('toggle');
        });
        
        // Special handling for pool details buttons
        $('.pool-details-btn').on('click', function() {
            var target = $(this).data('target');
            console.log("Toggling pool details:", target);
            $(target).collapse('toggle');
        });
    });
</script>

<!-- Navbar dynamic loading -->
<script>
    $(document).ready(function() {
        $("#nav-placeholder").load("../includes/navbar.html");
        togglePlaceholderFields(); // Initialize visibility of placeholder fields
    });
    
    function togglePlaceholderFields() {
        var category = document.getElementById('game_category').value;
        var placeholderFields = document.getElementsByClassName('placeholder-field');
        var infoBox = document.getElementById('game-category-info');
        
        if (category === 'pool') {
            // Hide placeholder fields for pool games
            for (var i = 0; i < placeholderFields.length; i++) {
                placeholderFields[i].style.display = 'none';
            }
            infoBox.style.display = 'none';
        } else {
            // Show placeholder fields for non-pool games
            for (var i = 0; i < placeholderFields.length; i++) {
                placeholderFields[i].style.display = 'block';
            }
            infoBox.style.display = 'block';
        }
    }
</script>

<script>
$(function() {
    // Initial toggle of relevant fields based on game category
    toggleFieldsBasedOnCategory();
    
    // Set up event handler for game category changes
    $('#game_category').on('change', function() {
        toggleFieldsBasedOnCategory();
    });
    
    // Set up event handlers for placeholder dropdowns
    $("#placeholder_home").on('change', function() {
        toggleGameIdInput('home');
    });
    
    $("#placeholder_away").on('change', function() {
        toggleGameIdInput('away');
    });
    
    // Handle the game ID inputs
    $("#home_game_id").on('input', function() {
        updatePlaceholderValue('home');
    });
    
    $("#away_game_id").on('input', function() {
        updatePlaceholderValue('away');
    });
    
    // Handle the custom text inputs
    $("#home_custom_text").on('input', function() {
        updateCustomPlaceholderValue('home');
    });
    
    $("#away_custom_text").on('input', function() {
        updateCustomPlaceholderValue('away');
    });
    
    // Initialize the game ID and custom text inputs on page load
    toggleGameIdInput('home');
    toggleGameIdInput('away');
    
    // Handle form submission to ensure custom values are set
    $("form").on('submit', function() {
        // If custom is selected, make sure the value is set correctly
        if ($("#placeholder_home").val() === 'custom') {
            const customText = $("#home_custom_text").val();
            if (customText) {
                // Find or create the custom option
                updateCustomPlaceholderValue('home');
            }
        }
        
        if ($("#placeholder_away").val() === 'custom') {
            const customText = $("#away_custom_text").val();
            if (customText) {
                // Find or create the custom option
                updateCustomPlaceholderValue('away');
            }
        }
        
        // Continue with form submission
        return true;
    });
});

function toggleFieldsBasedOnCategory() {
    const category = $('#game_category').val();
    
    // Toggle placeholder fields visibility based on game category
    if (category === 'pool') {
        $('.placeholder-field').hide();
        $('.team-field').show();
    } else {
        $('.placeholder-field').show();
        $('.team-field').hide();
    }
}

// Toggle game ID input field visibility
function toggleGameIdInput(type) {
    const selectEl = document.getElementById('placeholder_' + type);
    const gameIdContainer = document.getElementById(type + '-game-id-container');
    const customContainer = document.getElementById(type + '-custom-container');
    
    // Hide both containers first
    gameIdContainer.style.display = 'none';
    customContainer.style.display = 'none';
    
    if (selectEl.value === 'Winner of Game #') {
        // Show game ID input
        gameIdContainer.style.display = 'block';
        // Initialize with a default game ID if empty
        if (document.getElementById(type + '_game_id').value === '') {
            document.getElementById(type + '_game_id').value = '1';
            updatePlaceholderValue(type);
        }
    } else if (selectEl.value === 'custom') {
        // Show custom text input
        customContainer.style.display = 'block';
        // When custom is selected, copy any existing value to the hidden field
        updateCustomPlaceholderValue(type);
    }
}

// Update the placeholder value with the complete game ID reference
function updatePlaceholderValue(type) {
    const gameId = document.getElementById(type + '_game_id').value;
    const selectEl = document.getElementById('placeholder_' + type);
    
    if (selectEl.value === 'Winner of Game #' && gameId) {
        // Create a new option if it doesn't exist
        let customOption = null;
        const customValue = 'Winner of Game #' + gameId;
        
        // Check if custom option already exists
        for (let i = 0; i < selectEl.options.length; i++) {
            if (selectEl.options[i].value.startsWith('Winner of Game #') && 
                selectEl.options[i].value !== 'Winner of Game #') {
                customOption = selectEl.options[i];
                break;
            }
        }
        
        if (customOption) {
            // Update existing option
            customOption.value = customValue;
            customOption.text = customValue;
        } else {
            // Create new option
            customOption = new Option(customValue, customValue);
            selectEl.add(customOption);
        }
        
        // Select the custom option
        customOption.selected = true;
    }
}

// Update the placeholder value with custom text
function updateCustomPlaceholderValue(type) {
    const customText = document.getElementById(type + '_custom_text').value;
    const selectEl = document.getElementById('placeholder_' + type);
    
    if (selectEl.value === 'custom' && customText) {
        // Create a new option for the custom text
        let customOption = null;
        
        // Check if custom text option already exists
        for (let i = 0; i < selectEl.options.length; i++) {
            if (selectEl.options[i].value !== 'custom' && 
                selectEl.options[i].value !== 'Winner of Game #' && 
                !selectEl.options[i].value.startsWith('Winner of Game #') &&
                !<?php echo json_encode($placeholderOptions); ?>.includes(selectEl.options[i].value)) {
                customOption = selectEl.options[i];
                break;
            }
        }
        
        if (customOption) {
            // Update existing option
            customOption.value = customText;
            customOption.text = 'Custom: ' + customText;
        } else {
            // Create new option
            customOption = new Option('Custom: ' + customText, customText);
            selectEl.add(customOption);
        }
        
        // Select the custom option
        customOption.selected = true;
    }
}
</script>

</body>
</html> 
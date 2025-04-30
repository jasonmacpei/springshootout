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
$errors = [];
$success = false;
$message = '';

// Get all divisions
try {
    $divisionsStmt = $pdo->query("
        SELECT DISTINCT r.division
        FROM registrations r
        JOIN teams t ON r.team_id = t.team_id
        WHERE r.year = 2025 AND r.status = 1
        ORDER BY r.division
    ");
    $divisions = $divisionsStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $errors[] = "Error fetching divisions: " . $e->getMessage();
    $divisions = [];
}

// Get pool information for each division
$divisionPools = [];
foreach ($divisions as $division) {
    try {
        $poolsStmt = $pdo->prepare("
            SELECT p.pool_id, p.pool_name, COUNT(tp.team_id) AS team_count
            FROM pools p
            JOIN team_pools tp ON p.pool_id = tp.pool_id
            JOIN teams t ON tp.team_id = t.team_id
            JOIN registrations r ON t.team_id = r.team_id
            WHERE r.division = :division AND r.year = 2025 AND r.status = 1
            GROUP BY p.pool_id, p.pool_name
            ORDER BY p.pool_name
        ");
        $poolsStmt->execute([':division' => $division]);
        $divisionPools[$division] = $poolsStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errors[] = "Error fetching pools for division $division: " . $e->getMessage();
        $divisionPools[$division] = [];
    }
}

/**
 * Get existing playoff games for a division to use as placeholders
 * 
 * @param PDO $pdo Database connection
 * @param string $division Division code (e.g., 'u11')
 * @return array Array of game data with team information
 */
function getPlayoffGamesByDivision($pdo, $division) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                s.game_id,
                s.game_category,
                h.team_name AS home_team_name,
                a.team_name AS away_team_name,
                s.placeholder_home,
                s.placeholder_away
            FROM 
                schedule s
            LEFT JOIN 
                teams h ON s.home_team_id = h.team_id
            LEFT JOIN 
                teams a ON s.away_team_id = a.team_id
            WHERE 
                s.game_type = :division 
                AND s.game_category != 'pool'
            ORDER BY 
                s.game_id DESC
        ");
        $stmt->execute([':division' => $division]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching playoff games for division $division: " . $e->getMessage());
        return [];
    }
}

// List of gyms for dropdown
$gyms = ['Town Hall', 'Donagh', 'Stonepark', 'Rural', 'Glen Stewart', 'Colonel Grey', 'UPEI'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle creation of playoff games
    if (isset($_POST['create_playoffs'])) {
        $division = $_POST['division'] ?? '';
        $playoffStructure = $_POST['playoff_structure'] ?? '';
        $playoffDate = $_POST['playoff_date'] ?? '';
        $startTime = $_POST['start_time'] ?? '';
        $gym = $_POST['gym'] ?? '';
        
        // Basic validation
        if (empty($division)) {
            $errors[] = "Division is required";
        }
        if (empty($playoffStructure)) {
            $errors[] = "Playoff structure is required";
        }
        if (empty($playoffDate)) {
            $errors[] = "Date is required";
        }
        if (empty($startTime)) {
            $errors[] = "Start time is required";
        }
        if (empty($gym)) {
            $errors[] = "Gym is required";
        }
        
        // If no errors, create the playoff games
        if (empty($errors)) {
            try {
                $newGameIds = [];
                
                // Use the selected structure to create games
                switch ($playoffStructure) {
                    case 'crossover_2pools':
                        // Create crossover games for 2 pools: A1 vs B2, A2 vs B1
                        $timeSlots = [
                            $startTime,
                            date('H:i:s', strtotime($startTime) + 3600) // 1 hour later
                        ];
                        
                        $games = [
                            [
                                'home' => 'A1',
                                'away' => 'B2',
                                'category' => 'crossover',
                                'time' => $timeSlots[0]
                            ],
                            [
                                'home' => 'A2',
                                'away' => 'B1',
                                'category' => 'crossover',
                                'time' => $timeSlots[1]
                            ]
                        ];
                        
                        foreach ($games as $game) {
                            $stmt = $pdo->prepare("
                                INSERT INTO schedule (
                                    game_date, game_time, gym, game_type, 
                                    placeholder_home, placeholder_away, game_category
                                ) VALUES (
                                    :game_date, :game_time, :gym, :game_type, 
                                    :placeholder_home, :placeholder_away, :game_category
                                )
                            ");
                            
                            $params = [
                                ':game_date' => $playoffDate,
                                ':game_time' => $game['time'],
                                ':gym' => $gym,
                                ':game_type' => $division,
                                ':placeholder_home' => $game['home'],
                                ':placeholder_away' => $game['away'],
                                ':game_category' => $game['category']
                            ];
                            
                            $stmt->execute($params);
                            $newGameIds[] = $pdo->lastInsertId();
                        }
                        
                        $message = "Created 2 crossover games for $division division";
                        break;
                        
                    case 'semifinals_2pools':
                        // Create semifinal games using winners from crossovers
                        $timeSlots = [
                            $startTime,
                            date('H:i:s', strtotime($startTime) + 3600) // 1 hour later
                        ];
                        
                        // First, find the crossover games to reference
                        $crossoverStmt = $pdo->prepare("
                            SELECT game_id
                            FROM schedule
                            WHERE game_type = :division
                            AND game_category = 'crossover'
                            ORDER BY game_id
                        ");
                        $crossoverStmt->execute([':division' => $division]);
                        $crossoverGames = $crossoverStmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        // Create the games with winning references
                        if (count($crossoverGames) >= 2) {
                            $games = [
                                [
                                    'home' => 'Winner of Game #' . $crossoverGames[0],
                                    'away' => 'Winner of Game #' . $crossoverGames[1],
                                    'category' => 'semifinal',
                                    'time' => $timeSlots[0]
                                ]
                            ];
                            
                            foreach ($games as $game) {
                                $stmt = $pdo->prepare("
                                    INSERT INTO schedule (
                                        game_date, game_time, gym, game_type, 
                                        placeholder_home, placeholder_away, game_category
                                    ) VALUES (
                                        :game_date, :game_time, :gym, :game_type, 
                                        :placeholder_home, :placeholder_away, :game_category
                                    )
                                ");
                                
                                $params = [
                                    ':game_date' => $playoffDate,
                                    ':game_time' => $game['time'],
                                    ':gym' => $gym,
                                    ':game_type' => $division,
                                    ':placeholder_home' => $game['home'],
                                    ':placeholder_away' => $game['away'],
                                    ':game_category' => $game['category']
                                ];
                                
                                $stmt->execute($params);
                                $newGameIds[] = $pdo->lastInsertId();
                            }
                            
                            $message = "Created semifinal game for $division division";
                        } else {
                            $errors[] = "No crossover games found to reference for semifinals";
                        }
                        break;
                        
                    case 'finals':
                        // Create final game
                        $finalStmt = $pdo->prepare("
                            INSERT INTO schedule (
                                game_date, game_time, gym, game_type, 
                                placeholder_home, placeholder_away, game_category
                            ) VALUES (
                                :game_date, :game_time, :gym, :game_type, 
                                :placeholder_home, :placeholder_away, :game_category
                            )
                        ");
                        
                        $semifinalsStmt = $pdo->prepare("
                            SELECT game_id
                            FROM schedule
                            WHERE game_type = :division
                            AND game_category = 'semifinal'
                            ORDER BY game_id
                        ");
                        $semifinalsStmt->execute([':division' => $division]);
                        $semifinalGames = $semifinalsStmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        if (!empty($semifinalGames)) {
                            $placeholder_home = 'Winner of Game #' . $semifinalGames[0];
                            $placeholder_away = count($semifinalGames) > 1 ? 'Winner of Game #' . $semifinalGames[1] : 'TBD';
                        } else {
                            $placeholder_home = 'A1';
                            $placeholder_away = 'B1';
                        }
                        
                        $params = [
                            ':game_date' => $playoffDate,
                            ':game_time' => $startTime,
                            ':gym' => $gym,
                            ':game_type' => $division,
                            ':placeholder_home' => $placeholder_home,
                            ':placeholder_away' => $placeholder_away,
                            ':game_category' => 'final'
                        ];
                        
                        $finalStmt->execute($params);
                        $newGameIds[] = $pdo->lastInsertId();
                        
                        $message = "Created final game for $division division";
                        break;
                    
                    case 'custom':
                        // Custom game with user-provided placeholders
                        $placeholderHome = $_POST['placeholder_home'] ?? '';
                        $placeholderAway = $_POST['placeholder_away'] ?? '';
                        $gameCategory = $_POST['game_category'] ?? 'crossover';
                        
                        if (empty($placeholderHome) || empty($placeholderAway)) {
                            $errors[] = "Both home and away placeholders are required for custom games";
                        } else {
                            $customStmt = $pdo->prepare("
                                INSERT INTO schedule (
                                    game_date, game_time, gym, game_type, 
                                    placeholder_home, placeholder_away, game_category
                                ) VALUES (
                                    :game_date, :game_time, :gym, :game_type, 
                                    :placeholder_home, :placeholder_away, :game_category
                                )
                            ");
                            
                            $params = [
                                ':game_date' => $playoffDate,
                                ':game_time' => $startTime,
                                ':gym' => $gym,
                                ':game_type' => $division,
                                ':placeholder_home' => $placeholderHome,
                                ':placeholder_away' => $placeholderAway,
                                ':game_category' => $gameCategory
                            ];
                            
                            $customStmt->execute($params);
                            $newGameIds[] = $pdo->lastInsertId();
                            
                            $message = "Created custom $gameCategory game for $division division";
                        }
                        break;
                }
                
                $success = !empty($newGameIds);
                
                if ($success) {
                    $_SESSION['message'] = $message;
                    
                    // Try to automatically resolve placeholders
                    autoUpdatePlayoffGames($pdo);
                }
            } catch (PDOException $e) {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Count existing playoff games by division and category
$playoffCountsByDivision = [];
foreach ($divisions as $division) {
    try {
        $countStmt = $pdo->prepare("
            SELECT game_category, COUNT(*) as game_count
            FROM schedule
            WHERE game_type = :division AND game_category != 'pool'
            GROUP BY game_category
            ORDER BY CASE
                WHEN game_category = 'crossover' THEN 1
                WHEN game_category = 'quarterfinal' THEN 2
                WHEN game_category = 'semifinal' THEN 3
                WHEN game_category = 'final' THEN 4
                ELSE 5
            END
        ");
        $countStmt->execute([':division' => $division]);
        $playoffCountsByDivision[$division] = $countStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (PDOException $e) {
        $errors[] = "Error counting playoff games for $division: " . $e->getMessage();
        $playoffCountsByDivision[$division] = [];
    }
}

// Generate pool-based placeholder options
$placeholderOptionsByDivision = [];
foreach ($divisionPools as $division => $pools) {
    $options = [];
    foreach ($pools as $pool) {
        $poolLetter = $pool['pool_name'];
        for ($i = 1; $i <= $pool['team_count']; $i++) {
            $options[] = $poolLetter . $i;
        }
    }
    $placeholderOptionsByDivision[$division] = $options;
}

// Generate game winner options for each division
$gameWinnerOptionsByDivision = [];
foreach ($divisions as $division) {
    $gameWinnerOptionsByDivision[$division] = [];
    $games = getPlayoffGamesByDivision($pdo, $division);
    
    foreach ($games as $game) {
        $gameId = $game['game_id'];
        $category = ucfirst($game['game_category']);
        
        // Create a descriptive label based on available team information
        $description = "$category Game #$gameId: ";
        
        if (!empty($game['home_team_name']) && !empty($game['away_team_name'])) {
            // If both teams are known, use their names
            $description .= "{$game['home_team_name']} vs {$game['away_team_name']}";
        } elseif (!empty($game['placeholder_home']) && !empty($game['placeholder_away'])) {
            // If using placeholders, use those
            $description .= "{$game['placeholder_home']} vs {$game['placeholder_away']}";
        } else {
            // Mixed case or incomplete info
            $homeName = !empty($game['home_team_name']) ? $game['home_team_name'] : $game['placeholder_home'];
            $awayName = !empty($game['away_team_name']) ? $game['away_team_name'] : $game['placeholder_away'];
            $description .= "$homeName vs $awayName";
        }
        
        $gameWinnerOptionsByDivision[$division][$gameId] = [
            'value' => "Winner of Game #$gameId",
            'label' => $description
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Playoff Setup</title>
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
        .form-group label {
            color: white;
        }
        .card {
            background-color: #343a40;
            margin-bottom: 20px;
        }
        .playoff-summary {
            background-color: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .badge-crossover {
            background-color: #fd7e14;
        }
        .badge-quarterfinal {
            background-color: #17a2b8;
        }
        .badge-semifinal {
            background-color: #6f42c1;
        }
        .badge-final {
            background-color: #dc3545;
        }
        .badge-consolation-semifinal {
            background-color: #6c757d;
        }
        .badge-consolation-final {
            background-color: #28a745;
        }
        .custom-game-fields {
            display: none;
            background-color: rgba(255, 255, 255, 0.05);
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
        }
        .playoff-structure-info {
            margin-top: 5px;
            font-size: 0.9em;
            color: #aaa;
        }
        
        /* Dropdown optgroup styling */
        optgroup {
            font-weight: bold;
            color: #f8f9fa;
            background-color: #343a40;
            padding: 5px;
        }
        
        optgroup option {
            font-weight: normal;
            padding-left: 15px;
            color: #f8f9fa;
        }
        
        /* Make the dropdown a bit taller to accommodate more options */
        #placeholder_home, #placeholder_away {
            max-height: 300px;
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
    <h1 class="mt-4">Playoff Setup</h1>
    
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
            <?php echo htmlspecialchars($message); ?>
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
    
    <!-- Summary of existing playoff games -->
    <div class="playoff-summary">
        <h3>Playoff Games Summary</h3>
        <?php foreach ($divisions as $division): ?>
            <div class="mb-3">
                <h4><?php echo htmlspecialchars(strtoupper($division)); ?> Division</h4>
                
                <?php if (empty($playoffCountsByDivision[$division])): ?>
                    <p>No playoff games created yet.</p>
                <?php else: ?>
                    <p>
                        <?php foreach ($playoffCountsByDivision[$division] as $category => $count): ?>
                            <span class="badge badge-<?php echo $category; ?> mr-2">
                                <?php echo ucfirst(htmlspecialchars($category)); ?>: <?php echo $count; ?>
                            </span>
                        <?php endforeach; ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Playoff setup form -->
    <div class="card">
        <div class="card-header">
            <h3>Create Playoff Games</h3>
        </div>
        <div class="card-body">
            <form method="post" action="">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="division">Division</label>
                        <select class="form-control" id="division" name="division" required onchange="updatePlaceholders()">
                            <option value="">Select Division</option>
                            <?php foreach ($divisions as $division): ?>
                                <option value="<?php echo htmlspecialchars($division); ?>">
                                    <?php echo htmlspecialchars(strtoupper($division)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group col-md-6">
                        <label for="playoff_structure">Playoff Structure</label>
                        <select class="form-control" id="playoff_structure" name="playoff_structure" required onchange="toggleCustomFields()">
                            <option value="">Select Structure</option>
                            <option value="crossover_2pools">Crossovers (2 Pools: A1 vs B2, A2 vs B1)</option>
                            <option value="semifinals_2pools">Semifinals (Winners of Crossovers)</option>
                            <option value="finals">Finals</option>
                            <option value="custom">Custom Game</option>
                        </select>
                        <div id="structure-info" class="playoff-structure-info"></div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="playoff_date">Game Date</label>
                        <input type="date" class="form-control" id="playoff_date" name="playoff_date" required>
                    </div>
                    
                    <div class="form-group col-md-4">
                        <label for="start_time">Start Time</label>
                        <input type="time" class="form-control" id="start_time" name="start_time" required>
                    </div>
                    
                    <div class="form-group col-md-4">
                        <label for="gym">Gym</label>
                        <select class="form-control" id="gym" name="gym" required>
                            <option value="">Select Gym</option>
                            <?php foreach ($gyms as $gym): ?>
                                <option value="<?php echo htmlspecialchars($gym); ?>">
                                    <?php echo htmlspecialchars($gym); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Custom game fields (only shown when custom structure is selected) -->
                <div id="custom-game-fields" class="custom-game-fields">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="placeholder_home">Home Team</label>
                            <select class="form-control" id="placeholder_home" name="placeholder_home">
                                <option value="">Select Placeholder</option>
                                <!-- Options will be populated by JavaScript -->
                            </select>
                        </div>
                        
                        <div class="form-group col-md-4">
                            <label for="placeholder_away">Away Team</label>
                            <select class="form-control" id="placeholder_away" name="placeholder_away">
                                <option value="">Select Placeholder</option>
                                <!-- Options will be populated by JavaScript -->
                            </select>
                        </div>
                        
                        <div class="form-group col-md-4">
                            <label for="game_category">Game Category</label>
                            <select class="form-control" id="game_category" name="game_category">
                                <option value="crossover">Crossover</option>
                                <option value="quarterfinal">Quarterfinal</option>
                                <option value="semifinal">Semifinal</option>
                                <option value="consolation-semifinal">Consolation Semifinal</option>
                                <option value="consolation-final">Consolation Final</option>
                                <option value="final">Final</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <button type="submit" name="create_playoffs" class="btn btn-primary">Create Playoff Games</button>
                <a href="edit_schedule.php" class="btn btn-secondary">Back to Schedule Editor</a>
            </form>
        </div>
    </div>
    
    <div class="my-4">
        <a href="menu.php" class="btn btn-secondary">Back to Menu</a>
    </div>
</div>

<!-- jQuery and Bootstrap JS -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<!-- Navbar dynamic loading -->
<script>
    $(document).ready(function() {
        $("#nav-placeholder").load("../includes/navbar.html");
        
        // Initialize placeholders if a division is already selected
        updatePlaceholders();
    });
    
    // Store placeholder options by division
    const placeholderOptions = <?php echo json_encode($placeholderOptionsByDivision); ?>;
    
    // Store game winner options by division
    const gameWinnerOptions = <?php echo json_encode($gameWinnerOptionsByDivision); ?>;
    
    // Toggle custom game fields when playoff structure changes
    function toggleCustomFields() {
        const structure = document.getElementById('playoff_structure').value;
        const customFields = document.getElementById('custom-game-fields');
        const structureInfo = document.getElementById('structure-info');
        
        if (structure === 'custom') {
            customFields.style.display = 'block';
            structureInfo.textContent = 'Create a custom playoff game with specific placeholders.';
        } else {
            customFields.style.display = 'none';
            
            // Show info about the selected structure
            switch (structure) {
                case 'crossover_2pools':
                    structureInfo.textContent = 'Creates 2 crossover games: A1 vs B2 and A2 vs B1.';
                    break;
                case 'semifinals_2pools':
                    structureInfo.textContent = 'Creates semifinal game with winners of previous crossover games.';
                    break;
                case 'finals':
                    structureInfo.textContent = 'Creates final game with winners of semifinal games.';
                    break;
                default:
                    structureInfo.textContent = '';
            }
        }
    }
    
    // Update placeholder options when division changes
    function updatePlaceholders() {
        const division = document.getElementById('division').value;
        const homePlaceholder = document.getElementById('placeholder_home');
        const awayPlaceholder = document.getElementById('placeholder_away');
        
        // Clear existing options
        homePlaceholder.innerHTML = '<option value="">Select Placeholder</option>';
        awayPlaceholder.innerHTML = '<option value="">Select Placeholder</option>';
        
        if (!division) return;
        
        // Create option groups
        const homePoolGroup = document.createElement('optgroup');
        homePoolGroup.label = 'Pool Positions';
        
        const awayPoolGroup = document.createElement('optgroup');
        awayPoolGroup.label = 'Pool Positions';
        
        // Add pool placeholder options
        if (placeholderOptions[division]) {
            placeholderOptions[division].forEach(option => {
                // For home dropdown
                const homeOption = document.createElement('option');
                homeOption.value = option;
                homeOption.textContent = option;
                homePoolGroup.appendChild(homeOption);
                
                // For away dropdown
                const awayOption = document.createElement('option');
                awayOption.value = option;
                awayOption.textContent = option;
                awayPoolGroup.appendChild(awayOption);
            });
            
            // Add the option groups to the dropdowns
            homePlaceholder.appendChild(homePoolGroup);
            awayPlaceholder.appendChild(awayPoolGroup);
        }
        
        // Add game winner options if available
        if (gameWinnerOptions[division] && Object.keys(gameWinnerOptions[division]).length > 0) {
            // Create option groups for game winners
            const homeGameGroup = document.createElement('optgroup');
            homeGameGroup.label = 'Game Winners';
            
            const awayGameGroup = document.createElement('optgroup');
            awayGameGroup.label = 'Game Winners';
            
            // Add each game winner option
            Object.values(gameWinnerOptions[division]).forEach(gameOption => {
                // For home dropdown
                const homeGameOption = document.createElement('option');
                homeGameOption.value = gameOption.value;
                homeGameOption.textContent = gameOption.label;
                homeGameGroup.appendChild(homeGameOption);
                
                // For away dropdown
                const awayGameOption = document.createElement('option');
                awayGameOption.value = gameOption.value;
                awayGameOption.textContent = gameOption.label;
                awayGameGroup.appendChild(awayGameOption);
            });
            
            // Add the game winner groups to the dropdowns
            homePlaceholder.appendChild(homeGameGroup);
            awayPlaceholder.appendChild(awayGameGroup);
        }
    }
</script>

</body>
</html> 
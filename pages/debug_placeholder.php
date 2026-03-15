<?php
session_start(); // Start the session

// Check if user is logged in as admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || 
    !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: admin.php');
    exit;
}

// Include the configuration file and database connection
require_once __DIR__ . '/../includes/config.php';
require __DIR__ . '/../scripts/php/db_connect.php';
require_once __DIR__ . '/../scripts/php/resolve_placeholder.php';

// Check if all pool games are complete
$poolGamesComplete = areAllPoolGamesComplete($pdo);

// Get all playoff games with placeholders
$stmt = $pdo->prepare("
    SELECT 
        game_id, 
        game_type,
        game_category,
        placeholder_home, 
        placeholder_away
    FROM 
        schedule
    WHERE 
        game_category != 'pool' AND
        (placeholder_home IS NOT NULL OR placeholder_away IS NOT NULL)
    ORDER BY game_id
");
$stmt->execute();
$playoffGames = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get pool game completion statistics
$poolStmt = $pdo->prepare("
    SELECT 
        COUNT(*) AS total_pool_games,
        SUM(CASE WHEN gr.game_id IS NOT NULL THEN 1 ELSE 0 END) AS completed_pool_games
    FROM 
        schedule s
    LEFT JOIN 
        game_results gr ON s.game_id = gr.game_id
    WHERE 
        s.game_category = 'pool'
");
$poolStmt->execute();
$poolStats = $poolStmt->fetch(PDO::FETCH_ASSOC);

// Function to check if a placeholder can be resolved
function checkPlaceholderResolution($pdo, $placeholder, $gameType = null) {
    // For "Winner of Game" placeholders
    if (preg_match('/Winner\s+of\s+Game\s+#?(\d+)/i', $placeholder, $matches)) {
        $gameId = $matches[1];
        $winner = resolveGameWinnerTeam($pdo, (int)$gameId);
        if ($winner) {
            return [
                'resolvable' => true,
                'team_name' => $winner['team_name'],
                'team_id' => $winner['team_id'],
                'reason' => 'Game has a winner'
            ];
        } else {
            // Check if the game exists and has teams assigned
            $gameStmt = $pdo->prepare("
                SELECT 
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
            
            if ($gameResult) {
                return [
                    'resolvable' => false,
                    'reason' => 'Game exists but no winner yet',
                    'game_details' => [
                        'home_team' => $gameResult['home_team_name'] ?? 'Not assigned',
                        'away_team' => $gameResult['away_team_name'] ?? 'Not assigned'
                    ]
                ];
            } else {
                return [
                    'resolvable' => false,
                    'reason' => 'Game not found'
                ];
            }
        }
    }
    
    // For pool position placeholders
    if ($poolGamesComplete = areAllPoolGamesComplete($pdo)) {
        $team = resolveTeamFromPlaceholder($pdo, $placeholder, 2025, $gameType, true);
        if (is_array($team) && isset($team['error_info'])) {
            return [
                'resolvable' => false,
                'reason' => $team['error_info']['message'],
                'details' => $team['error_info']
            ];
        } else if ($team) {
            return [
                'resolvable' => true,
                'team_name' => $team['team_name'],
                'team_id' => $team['team_id'],
                'matched_pool' => $team['matched_pool_name'] ?? null,
                'reason' => 'Pool games complete and team resolved'
            ];
        } else {
            return [
                'resolvable' => false,
                'reason' => 'Unable to resolve placeholder'
            ];
        }
    } else {
        return [
            'resolvable' => false,
            'reason' => 'Pool games not complete'
        ];
    }
}

// Process placeholders for each playoff game
$gameResolutions = [];
foreach ($playoffGames as $game) {
    $gameId = $game['game_id'];
    $gameType = $game['game_type'];
    
    $homeResolution = !empty($game['placeholder_home']) ? 
        checkPlaceholderResolution($pdo, $game['placeholder_home'], $gameType) : null;
    
    $awayResolution = !empty($game['placeholder_away']) ? 
        checkPlaceholderResolution($pdo, $game['placeholder_away'], $gameType) : null;
    
    $gameResolutions[$gameId] = [
        'game_id' => $gameId,
        'game_type' => $gameType,
        'game_category' => $game['game_category'],
        'home' => [
            'placeholder' => $game['placeholder_home'],
            'resolution' => $homeResolution
        ],
        'away' => [
            'placeholder' => $game['placeholder_away'],
            'resolution' => $awayResolution
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Placeholder Debug</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background-color: black;
            color: white;
            padding: 20px;
        }
        .card {
            background-color: #212529;
            color: white;
            margin-bottom: 20px;
            border: 1px solid #444;
        }
        .card-header {
            background-color: #2c3136;
            border-bottom: 1px solid #444;
        }
        .status-badge {
            margin-left: 10px;
        }
        .status-resolvable {
            background-color: #28a745;
        }
        .status-not-resolvable {
            background-color: #dc3545;
        }
        .placeholder-text {
            font-family: monospace;
            background-color: #333;
            padding: 2px 5px;
            border-radius: 3px;
        }
        .details-section {
            background-color: #333;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            font-family: monospace;
            font-size: 0.9rem;
        }
        .stat-card {
            text-align: center;
            padding: 15px;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
        }
        .progress {
            height: 20px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">Placeholder Resolution Debug</h1>
                <div class="mb-3">
                    <a href="./menu.php" class="btn btn-primary">Back to Admin Menu</a>
                </div>
            </div>
        </div>
        
        <!-- Pool Game Completion Stats -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card stat-card">
                    <h4>Pool Game Completion</h4>
                    <div class="stat-value"><?php echo $poolStats['completed_pool_games']; ?> / <?php echo $poolStats['total_pool_games']; ?></div>
                    <div class="progress">
                        <?php 
                        $percentage = $poolStats['total_pool_games'] > 0 ? 
                            ($poolStats['completed_pool_games'] / $poolStats['total_pool_games']) * 100 : 0;
                        ?>
                        <div class="progress-bar <?php echo $percentage == 100 ? 'bg-success' : 'bg-warning'; ?>" 
                             role="progressbar" 
                             style="width: <?php echo $percentage; ?>%;" 
                             aria-valuenow="<?php echo $percentage; ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            <?php echo round($percentage); ?>%
                        </div>
                    </div>
                    <div class="mt-2">
                        <?php if ($poolGamesComplete): ?>
                            <span class="badge badge-success">All Pool Games Complete</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Pool Play In Progress</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card stat-card">
                    <h4>Playoff Games with Placeholders</h4>
                    <div class="stat-value"><?php echo count($playoffGames); ?></div>
                    <div class="mt-3">
                        <a href="#" class="btn btn-outline-light btn-sm" onclick="$('.card-details').collapse('show'); return false;">Show All Details</a>
                        <a href="#" class="btn btn-outline-light btn-sm" onclick="$('.card-details').collapse('hide'); return false;">Hide All Details</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Playoff Games List -->
        <div class="row">
            <div class="col-12">
                <h2>Playoff Game Placeholders</h2>
                
                <?php foreach ($gameResolutions as $gameId => $game): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            Game #<?php echo $gameId; ?> (<?php echo $game['game_type']; ?>, <?php echo $game['game_category']; ?>)
                            <button class="btn btn-link text-white float-right" type="button" data-toggle="collapse" data-target="#collapse<?php echo $gameId; ?>">
                                <span class="toggle-icon">+</span> Details
                            </button>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Home Team: 
                                    <span class="placeholder-text"><?php echo $game['home']['placeholder'] ?: 'No placeholder'; ?></span>
                                    <?php if (!empty($game['home']['resolution'])): ?>
                                    <span class="badge <?php echo $game['home']['resolution']['resolvable'] ? 'status-resolvable' : 'status-not-resolvable'; ?> status-badge">
                                        <?php echo $game['home']['resolution']['resolvable'] ? 'Resolvable' : 'Not Resolvable'; ?>
                                    </span>
                                    <?php endif; ?>
                                </h6>
                                <?php if (!empty($game['home']['resolution'])): ?>
                                <p>Reason: <?php echo $game['home']['resolution']['reason']; ?></p>
                                <?php if ($game['home']['resolution']['resolvable']): ?>
                                <p>Resolves to: <strong><?php echo $game['home']['resolution']['team_name']; ?></strong></p>
                                <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <h6>Away Team: 
                                    <span class="placeholder-text"><?php echo $game['away']['placeholder'] ?: 'No placeholder'; ?></span>
                                    <?php if (!empty($game['away']['resolution'])): ?>
                                    <span class="badge <?php echo $game['away']['resolution']['resolvable'] ? 'status-resolvable' : 'status-not-resolvable'; ?> status-badge">
                                        <?php echo $game['away']['resolution']['resolvable'] ? 'Resolvable' : 'Not Resolvable'; ?>
                                    </span>
                                    <?php endif; ?>
                                </h6>
                                <?php if (!empty($game['away']['resolution'])): ?>
                                <p>Reason: <?php echo $game['away']['resolution']['reason']; ?></p>
                                <?php if ($game['away']['resolution']['resolvable']): ?>
                                <p>Resolves to: <strong><?php echo $game['away']['resolution']['team_name']; ?></strong></p>
                                <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="collapse card-details" id="collapse<?php echo $gameId; ?>">
                            <div class="details-section mt-3">
                                <h6>Detailed Resolution Information:</h6>
                                
                                <?php if (!empty($game['home']['resolution'])): ?>
                                <div class="mb-3">
                                    <strong>Home Resolution Details:</strong>
                                    <pre><?php print_r($game['home']['resolution']); ?></pre>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($game['away']['resolution'])): ?>
                                <div>
                                    <strong>Away Resolution Details:</strong>
                                    <pre><?php print_r($game['away']['resolution']); ?></pre>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($gameResolutions)): ?>
                <div class="alert alert-info">No playoff games with placeholders found.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- JavaScript for collapsible sections -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.collapse').on('show.bs.collapse', function() {
                $(this).parent().find('.toggle-icon').text('-');
            });
            
            $('.collapse').on('hide.bs.collapse', function() {
                $(this).parent().find('.toggle-icon').text('+');
            });
        });
    </script>
</body>
</html> 
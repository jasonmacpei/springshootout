<?php
// Include database and authentication
session_start();
require __DIR__ . '/../scripts/php/db_connect.php';
require_once __DIR__ . '/../scripts/php/resolve_placeholder.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: admin.php');
    exit;
}

// Variables to hold form data and errors
$formData = [
    'home_team_id' => '',
    'away_team_id' => '',
    'game_time' => '',
    'game_date' => '2025-04-28',
    'gym' => '',
    'game_type' => '',
    'placeholder_home' => '',
    'placeholder_away' => '',
    'game_category' => 'pool'
];
$success_message = '';
$error_message = '';

// Define placeholder options
$placeholderOptions = ['A1', 'A2', 'A3', 'A4', 'B1', 'B2', 'B3', 'B4', 'C1', 'C2', 'C3', 'C4', 'D1', 'D2', 'D3', 'D4'];

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $formData['home_team_id'] = !empty($_POST['home_team_id']) ? $_POST['home_team_id'] : NULL;
    $formData['away_team_id'] = !empty($_POST['away_team_id']) ? $_POST['away_team_id'] : NULL;
    $formData['game_time'] = $_POST['game_time'];
    $formData['game_date'] = $_POST['game_date'];
    $formData['gym'] = $_POST['gym'];
    $formData['game_type'] = $_POST['game_type'];
    $formData['game_category'] = $_POST['game_category'] ?? 'pool';
    $formData['placeholder_home'] = !empty($_POST['placeholder_home']) ? $_POST['placeholder_home'] : NULL;
    $formData['placeholder_away'] = !empty($_POST['placeholder_away']) ? $_POST['placeholder_away'] : NULL;
    
    // Basic validation
    $errors = [];
    
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
        // For pool games, clear any placeholders
        $formData['placeholder_home'] = NULL;
        $formData['placeholder_away'] = NULL;
    }
    
    // If no errors, insert the game
    if (empty($errors)) {
        try {
            $query = "INSERT INTO schedule (
                home_team_id, away_team_id, game_time, game_date, gym, game_type,
                placeholder_home, placeholder_away, game_category
            ) VALUES (
                :home_team_id, :away_team_id, :game_time, :game_date, :gym, :game_type,
                :placeholder_home, :placeholder_away, :game_category
            )";
            
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':home_team_id', $formData['home_team_id']);
            $stmt->bindParam(':away_team_id', $formData['away_team_id']);
            $stmt->bindParam(':game_time', $formData['game_time']);
            $stmt->bindParam(':game_date', $formData['game_date']);
            $stmt->bindParam(':gym', $formData['gym']);
            $stmt->bindParam(':game_type', $formData['game_type']);
            $stmt->bindParam(':placeholder_home', $formData['placeholder_home']);
            $stmt->bindParam(':placeholder_away', $formData['placeholder_away']);
            $stmt->bindParam(':game_category', $formData['game_category']);
            
            $stmt->execute();
            $success_message = "Schedule created successfully.";
            
            // Try to automatically resolve placeholders
            if ($formData['game_category'] !== 'pool') {
                autoUpdatePlayoffGames($pdo);
            }
            
            // Reset form data after successful submission
            $formData = [
                'home_team_id' => '',
                'away_team_id' => '',
                'game_time' => '',
                'game_date' => '2025-04-28',
                'gym' => '',
                'game_type' => '',
                'placeholder_home' => '',
                'placeholder_away' => '',
                'game_category' => 'pool'
            ];
        } catch (PDOException $e) {
            $error_message = "Error creating schedule: " . $e->getMessage();
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Fetch teams from registrations table for select options
try {
    $stmt = $pdo->query("
    SELECT 
        r.team_id,
        t.team_name,
        r.division
    FROM registrations r 
    LEFT JOIN teams t ON r.team_id = t.team_id
    WHERE year = 2025 
    AND status = 1
    ORDER BY r.division, t.team_name
    ");
    $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching teams: " . $e->getMessage();
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
    $error_message = "Error fetching pools: " . $e->getMessage();
    $pools = [];
    $placeholderOptions = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Make Schedule</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom Stylesheet -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background-color: black;
            color: white;
        }
        .container {
            padding-top: 20px;
        }
        .navbar-brand img {
            width: auto;
            height: 30px;
        }
        .placeholder-note {
            font-size: 0.85em;
            color: #aaa;
            margin-top: 5px;
        }
        .game-category-info {
            background-color: rgba(255, 255, 255, 0.1);
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: none;
        }
        .form-section {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            background-color: rgba(255, 255, 255, 0.05);
        }
    </style>
</head>
<body>

<!-- Nav Bar below -->
<div class="container">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark justify-content-center">
        <div id="nav-placeholder"></div>
    </nav>
</div>

<!-- Logo -->
<div class="text-center">
    <img src="../assets/images/name.png" alt="Spring Shootout">
</div>

<!-- Form container -->
<div class="container">
    <h1 class="text-center mb-4">Create Game Schedule</h1>
    
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success" role="alert"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger" role="alert"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <!-- Schedule form -->
    <form method="POST" action="make_schedule.php">
        <div class="form-section">
            <h4>Game Details</h4>
            
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="game_date">Game Date</label>
                    <input type="date" id="game_date" name="game_date" class="form-control" 
                           required value="<?php echo htmlspecialchars($formData['game_date']); ?>">
                </div>
                
                <div class="form-group col-md-4">
                    <label for="game_time">Game Time</label>
                    <input type="time" name="game_time" id="game_time" class="form-control" 
                           value="<?php echo htmlspecialchars($formData['game_time']); ?>" required>
                </div>
                
                <div class="form-group col-md-4">
                    <label for="gym">Gym</label>
                    <select name="gym" id="gym" class="form-control" required>
                        <option value="" disabled <?php echo empty($formData['gym']) ? 'selected' : ''; ?>>Please choose a gym</option>
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
                    <select name="game_type" id="game_type" class="form-control" required>
                        <option value="" disabled <?php echo empty($formData['game_type']) ? 'selected' : ''; ?>>Please choose a game type</option>
                        <option value="u11" <?php echo $formData['game_type'] === 'u11' ? 'selected' : ''; ?>>u11</option>
                        <option value="u12" <?php echo $formData['game_type'] === 'u12' ? 'selected' : ''; ?>>u12</option>
                        <option value="u13" <?php echo $formData['game_type'] === 'u13' ? 'selected' : ''; ?>>u13</option>
                    </select>
                </div>
                
                <div class="form-group col-md-6">
                    <label for="game_category">Game Category</label>
                    <select name="game_category" id="game_category" class="form-control" onchange="toggleFieldsBasedOnCategory()">
                        <option value="pool" <?php echo $formData['game_category'] === 'pool' ? 'selected' : ''; ?>>Pool Play</option>
                        <option value="crossover" <?php echo $formData['game_category'] === 'crossover' ? 'selected' : ''; ?>>Crossover</option>
                        <option value="quarterfinal" <?php echo $formData['game_category'] === 'quarterfinal' ? 'selected' : ''; ?>>Quarterfinal</option>
                        <option value="semifinal" <?php echo $formData['game_category'] === 'semifinal' ? 'selected' : ''; ?>>Semifinal</option>
                        <option value="final" <?php echo $formData['game_category'] === 'final' ? 'selected' : ''; ?>>Final</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div id="game-category-info" class="game-category-info">
            <h5>Playoff Game Information</h5>
            <p>For playoff games, you can use either specific teams or placeholders:</p>
            <ul>
                <li><strong>Specific Teams:</strong> Select actual teams if they are already known</li>
                <li><strong>Placeholders:</strong> Use placeholder codes like "A1" (1st place in Pool A) if teams will be determined by standings</li>
            </ul>
            <p>Placeholders will automatically be replaced with actual teams once pool play is complete.</p>
        </div>
        
        <!-- Team selection -->
        <div class="form-section">
            <h4>Team Selection</h4>
            
            <div class="form-row">
                <div class="form-group col-md-5 team-field">
                    <label for="home_team_id">Home Team</label>
                    <select name="home_team_id" id="home_team_id" class="form-control">
                        <option value="">Select Home Team</option>
                        <?php foreach ($teams as $team): ?>
                            <option value="<?php echo $team['team_id']; ?>" <?php echo ($formData['home_team_id'] == $team['team_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($team['team_name']); ?> (<?php echo htmlspecialchars($team['division']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group col-md-5 team-field">
                    <label for="away_team_id">Away Team</label>
                    <select name="away_team_id" id="away_team_id" class="form-control">
                        <option value="">Select Away Team</option>
                        <?php foreach ($teams as $team): ?>
                            <option value="<?php echo $team['team_id']; ?>" <?php echo ($formData['away_team_id'] == $team['team_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($team['team_name']); ?> (<?php echo htmlspecialchars($team['division']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Placeholder fields (only shown for bracket games) -->
            <div class="form-group col-md-5 placeholder-field" style="display: none;">
                <label for="placeholder_home">Home Placeholder</label>
                <select class="form-control" id="placeholder_home" name="placeholder_home">
                    <option value="">Select Placeholder</option>
                    <?php foreach ($placeholderOptions as $option): ?>
                        <option value="<?php echo $option; ?>"><?php echo $option; ?></option>
                    <?php endforeach; ?>
                    <option value="Winner of Game #">Winner of a Previous Game</option>
                    <option value="custom">Custom Text</option>
                </select>
                <div class="placeholder-note">Example: A1 = 1st place in Pool A</div>
                
                <!-- Custom game ID input (only shown if "Winner of Game #" is selected) -->
                <div id="home-game-id-container" class="mt-2" style="display: none;">
                    <label for="home_game_id">Game ID</label>
                    <input type="text" class="form-control" id="home_game_id" placeholder="Enter Game ID">
                    <div class="placeholder-note">
                        This will create "Winner of Game #[ID]"
                    </div>
                </div>
                
                <!-- Custom text input (only shown if "Custom Text" is selected) -->
                <div id="home-custom-container" class="mt-2" style="display: none;">
                    <label for="home_custom_text">Custom Placeholder Text</label>
                    <input type="text" class="form-control" id="home_custom_text" placeholder="e.g., A1 vs B2">
                    <div class="placeholder-note">
                        Enter any text that describes this placeholder (e.g., "A1 vs B2", "Pool A Winner")
                    </div>
                </div>
            </div>
            
            <!-- Away Team Placeholder -->
            <div class="form-group col-md-5 placeholder-field" style="display: none;">
                <label for="placeholder_away">Away Placeholder</label>
                <select class="form-control" id="placeholder_away" name="placeholder_away">
                    <option value="">Select Placeholder</option>
                    <?php foreach ($placeholderOptions as $option): ?>
                        <option value="<?php echo $option; ?>"><?php echo $option; ?></option>
                    <?php endforeach; ?>
                    <option value="Winner of Game #">Winner of a Previous Game</option>
                    <option value="custom">Custom Text</option>
                </select>
                <div class="placeholder-note">Example: B2 = 2nd place in Pool B</div>
                
                <!-- Custom game ID input (only shown if "Winner of Game #" is selected) -->
                <div id="away-game-id-container" class="mt-2" style="display: none;">
                    <label for="away_game_id">Game ID</label>
                    <input type="text" class="form-control" id="away_game_id" placeholder="Enter Game ID">
                    <div class="placeholder-note">
                        This will create "Winner of Game #[ID]"
                    </div>
                </div>
                
                <!-- Custom text input (only shown if "Custom Text" is selected) -->
                <div id="away-custom-container" class="mt-2" style="display: none;">
                    <label for="away_custom_text">Custom Placeholder Text</label>
                    <input type="text" class="form-control" id="away_custom_text" placeholder="e.g., C1 vs D2">
                    <div class="placeholder-note">
                        Enter any text that describes this placeholder (e.g., "C1 vs D2", "Pool B Runner-up")
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Create Game</button>
            <a href="schedule.php" class="btn btn-secondary">Show Schedule</a>
            <a href="playoff_setup.php" class="btn btn-info">Playoff Setup Tool</a>
        </div>
    </form>
</div>

<!-- jQuery for dynamic content loading -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<!-- Script to dynamically load the navbar -->
<script>
    $(function() {
        $("#nav-placeholder").load("../includes/navbar.html");
        
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


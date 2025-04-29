<?php
// Start session and check if user is logged in
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: admin.php');
    exit;
}

require __DIR__ . '/../scripts/php/db_connect.php'; // Include the database connection

$success_message = '';
$error_message = '';
$selected_game = null;

// Handle form submission for updating a schedule entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_game'])) {
    $game_id = $_POST['game_id'];
    $home_team_id = !empty($_POST['home_team_id']) ? $_POST['home_team_id'] : NULL;
    $away_team_id = !empty($_POST['away_team_id']) ? $_POST['away_team_id'] : NULL;
    $game_time = $_POST['game_time'];
    $game_date = $_POST['game_date'];
    $gym = $_POST['gym'];
    $game_type = $_POST['game_type'];

    try {
        $stmt = $pdo->prepare("
            UPDATE schedule 
            SET home_team_id = :home_team_id, 
                away_team_id = :away_team_id, 
                game_time = :game_time, 
                game_date = :game_date, 
                gym = :gym, 
                game_type = :game_type 
            WHERE game_id = :game_id
        ");
        
        $stmt->execute([
            ':home_team_id' => $home_team_id,
            ':away_team_id' => $away_team_id,
            ':game_time' => $game_time,
            ':game_date' => $game_date,
            ':gym' => $gym,
            ':game_type' => $game_type,
            ':game_id' => $game_id
        ]);
        
        $success_message = "Schedule updated successfully.";
    } catch (PDOException $e) {
        $error_message = "Error updating schedule: " . $e->getMessage();
    }
}

// Handle form submission for deleting a schedule entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_game'])) {
    $game_id = $_POST['game_id'];
    
    try {
        // First check if this game has results associated with it
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM game_results WHERE game_id = :game_id");
        $checkStmt->execute([':game_id' => $game_id]);
        $hasResults = $checkStmt->fetchColumn() > 0;
        
        if ($hasResults) {
            $error_message = "Cannot delete this game because it has results associated with it. Please delete the results first.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM schedule WHERE game_id = :game_id");
            $stmt->execute([':game_id' => $game_id]);
            $success_message = "Schedule entry deleted successfully.";
        }
    } catch (PDOException $e) {
        $error_message = "Error deleting schedule entry: " . $e->getMessage();
    }
}

// Fetch all schedule entries for the dropdown
try {
    $stmt = $pdo->query("
        SELECT 
            s.game_id, 
            s.game_date, 
            s.game_time, 
            h.team_name AS home_team, 
            a.team_name AS away_team, 
            s.gym, 
            s.game_type
        FROM 
            schedule s
        LEFT JOIN 
            teams h ON s.home_team_id = h.team_id
        LEFT JOIN 
            teams a ON s.away_team_id = a.team_id
        ORDER BY 
            s.game_date, s.game_time
    ");
    $scheduleEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching schedule entries: " . $e->getMessage();
    $scheduleEntries = [];
}

// If a specific game is selected via GET or POST, fetch its details
if (isset($_GET['game_id']) || (isset($_POST['game_id']) && isset($_POST['load_game']))) {
    $game_id = isset($_GET['game_id']) ? $_GET['game_id'] : $_POST['game_id'];
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                s.game_id, 
                s.home_team_id, 
                s.away_team_id, 
                s.game_date, 
                s.game_time, 
                s.gym, 
                s.game_type
            FROM 
                schedule s
            WHERE 
                s.game_id = :game_id
        ");
        $stmt->execute([':game_id' => $game_id]);
        $selected_game = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "Error fetching game details: " . $e->getMessage();
    }
}

// Fetch teams from registrations table for select options
try {
    $stmt = $pdo->query("
    SELECT 
        r.team_id
        , t.team_name
    FROM registrations r 
    LEFT JOIN teams t ON r.team_id = t.team_id
        WHERE year = 2025 
        AND status = 1
    ORDER BY t.team_name;
    ");
    $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching teams: " . $e->getMessage();
    $teams = [];
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
        .btn-danger {
            margin-left: 10px;
        }
        .schedule-entry {
            cursor: pointer;
        }
        .schedule-entry:hover {
            background-color: rgba(255, 255, 255, 0.1);
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
    <h1 class="text-center mb-4">Edit Schedule</h1>
    
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success" role="alert"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger" role="alert"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <!-- Schedule entries table -->
    <div class="card bg-dark mb-4">
        <div class="card-header">
            <h4>Select a Game to Edit</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-dark table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Home Team</th>
                            <th>Away Team</th>
                            <th>Gym</th>
                            <th>Game Type</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($scheduleEntries as $entry): ?>
                            <tr class="schedule-entry">
                                <td><?php echo htmlspecialchars($entry['game_date']); ?></td>
                                <td><?php echo htmlspecialchars($entry['game_time']); ?></td>
                                <td><?php echo $entry['home_team'] ? htmlspecialchars($entry['home_team']) : 'TBD'; ?></td>
                                <td><?php echo $entry['away_team'] ? htmlspecialchars($entry['away_team']) : 'TBD'; ?></td>
                                <td><?php echo htmlspecialchars($entry['gym']); ?></td>
                                <td><?php echo htmlspecialchars($entry['game_type']); ?></td>
                                <td>
                                    <a href="?game_id=<?php echo $entry['game_id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($scheduleEntries)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No schedule entries found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit form -->
    <?php if ($selected_game): ?>
        <div class="card bg-dark">
            <div class="card-header">
                <h4>Edit Game</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="edit_schedule.php">
                    <input type="hidden" name="game_id" value="<?php echo $selected_game['game_id']; ?>">
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="home_team_id" class="text-white">Home Team:</label>
                            <select name="home_team_id" class="form-control">
                                <option value="">TBD</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?php echo htmlspecialchars($team['team_id']); ?>" <?php echo ($selected_game['home_team_id'] == $team['team_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($team['team_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group col-md-6">
                            <label for="away_team_id" class="text-white">Away Team:</label>
                            <select name="away_team_id" class="form-control">
                                <option value="">TBD</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?php echo htmlspecialchars($team['team_id']); ?>" <?php echo ($selected_game['away_team_id'] == $team['team_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($team['team_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="game_time" class="text-white">Game Time:</label>
                            <input type="time" name="game_time" class="form-control" value="<?php echo htmlspecialchars($selected_game['game_time']); ?>" required>
                        </div>

                        <div class="form-group col-md-6">
                            <label for="game_date" class="text-white">Game Date:</label>
                            <input type="date" id="game_date" name="game_date" class="form-control" value="<?php echo htmlspecialchars($selected_game['game_date']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="gym" class="text-white">Gym:</label>
                        <select name="gym" class="form-control" required>
                            <option value="" disabled>Please choose a gym</option>
                            <option value="Town Hall" <?php echo ($selected_game['gym'] == 'Town Hall') ? 'selected' : ''; ?>>Town Hall</option>
                            <option value="Donagh" <?php echo ($selected_game['gym'] == 'Donagh') ? 'selected' : ''; ?>>Donagh</option>
                            <option value="Stonepark" <?php echo ($selected_game['gym'] == 'Stonepark') ? 'selected' : ''; ?>>Stonepark</option>
                            <option value="Rural" <?php echo ($selected_game['gym'] == 'Rural') ? 'selected' : ''; ?>>Rural</option>
                            <option value="Glen Stewart" <?php echo ($selected_game['gym'] == 'Glen Stewart') ? 'selected' : ''; ?>>Glen Stewart</option>
                            <option value="Colonel Grey" <?php echo ($selected_game['gym'] == 'Colonel Grey') ? 'selected' : ''; ?>>Colonel Grey</option>
                            <option value="UPEI" <?php echo ($selected_game['gym'] == 'UPEI') ? 'selected' : ''; ?>>UPEI</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="game_type" class="text-white">Game Type:</label>
                        <select name="game_type" class="form-control" required>
                            <option value="" disabled>Please choose a game type</option>
                            <option value="u11" <?php echo ($selected_game['game_type'] == 'u11') ? 'selected' : ''; ?>>u11 Pool</option>
                            <option value="u12" <?php echo ($selected_game['game_type'] == 'u12') ? 'selected' : ''; ?>>u12 Pool</option>
                            <option value="u13" <?php echo ($selected_game['game_type'] == 'u13') ? 'selected' : ''; ?>>u13 Pool</option>
                            <option value="u10 A1 vs A2" <?php echo ($selected_game['game_type'] == 'u10 A1 vs A2') ? 'selected' : ''; ?>>u10 A1 vs A2</option>
                            <option value="u10 B1 vs B2" <?php echo ($selected_game['game_type'] == 'u10 B1 vs B2') ? 'selected' : ''; ?>>u10 B1 vs B2</option>
                            <option value="u10 A3 vs B3" <?php echo ($selected_game['game_type'] == 'u10 A3 vs B3') ? 'selected' : ''; ?>>u10 A3 vs B3</option>
                            <option value="u10 Championship" <?php echo ($selected_game['game_type'] == 'u10 Championship') ? 'selected' : ''; ?>>u10 Championship</option>
                            <option value="u12 C1 vs D2" <?php echo ($selected_game['game_type'] == 'u12 C1 vs D2') ? 'selected' : ''; ?>>u12 C1 vs D2</option>
                            <option value="u12 C2 vs D1" <?php echo ($selected_game['game_type'] == 'u12 C2 vs D1') ? 'selected' : ''; ?>>u12 C2 vs D1</option>
                            <option value="u12 C3 vs D3" <?php echo ($selected_game['game_type'] == 'u12 C3 vs D3') ? 'selected' : ''; ?>>u12 C3 vs D3</option>
                            <option value="u12 C4 vs D4" <?php echo ($selected_game['game_type'] == 'u12 C4 vs D4') ? 'selected' : ''; ?>>u12 C4 vs D4</option>
                            <option value="u12 Championship" <?php echo ($selected_game['game_type'] == 'u12 Championship') ? 'selected' : ''; ?>>u12 Championship</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <button type="submit" name="update_game" class="btn btn-primary">Update Game</button>
                        <button type="submit" name="delete_game" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this game? This action cannot be undone.');">Delete Game</button>
                        <a href="schedule.php" class="btn btn-secondary ml-2">View Schedule</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="mt-4 text-center">
        <a href="menu.php" class="btn btn-secondary">Back to Menu</a>
        <a href="make_schedule.php" class="btn btn-primary">Create New Game</a>
    </div>
</div>

<!-- jQuery for dynamic content loading -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<!-- Script to dynamically load the navbar -->
<script>
    $(function() {
        $("#nav-placeholder").load("../includes/navbar.html");
    });
</script>

</body>
</html> 
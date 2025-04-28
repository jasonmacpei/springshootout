<?php
//edit_results.php
require_once '/home/lostan6/springshootout.ca/includes/config.php';
require __DIR__ . '/../scripts/php/db_connect.php'; // Include the database connection

$games = [];
$selectedGame = null;
$success_message = '';
$error_message = '';

// Fetch games with results for dropdown
try {
    $stmt = $pdo->query("
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
   ORDER BY s.game_date, s.game_time, s.game_id
    ");
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching games: " . $e->getMessage();
}

// Check for POST request to update game result
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_result'])) {
    $game_id = $_POST['game_id'];
    $home_score = $_POST['home_score'];
    $away_score = $_POST['away_score'];

    // Validation code goes here, ensure $game_id, $home_score, and $away_score are valid

    try {
        $updateStmt = $pdo->prepare("UPDATE game_results SET points_for = :home_score, points_against = :away_score WHERE game_id = :game_id AND team_id = (SELECT home_team_id FROM schedule WHERE game_id = :game_id)");
        $updateStmt->execute([
            ':home_score' => $home_score,
            ':away_score' => $away_score,
            ':game_id' => $game_id
        ]);

        $updateStmt = $pdo->prepare("UPDATE game_results SET points_for = :away_score, points_against = :home_score WHERE game_id = :game_id AND team_id = (SELECT away_team_id FROM schedule WHERE game_id = :game_id)");
        $updateStmt->execute([
            ':away_score' => $away_score,
            ':home_score' => $home_score,
            ':game_id' => $game_id
        ]);

        $success_message = "Game result updated successfully.";
    } catch (PDOException $e) {
        $error_message = "Error updating game result: " . $e->getMessage();
    }
}

// Check for POST request to delete game result
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_result'])) {
    $game_id = $_POST['game_id'];

    // Validation code goes here, ensure $game_id is valid

    try {
        $deleteStmt = $pdo->prepare("DELETE FROM game_results WHERE game_id = :game_id");
        $deleteStmt->execute([':game_id' => $game_id]);

        $success_message = "Game result deleted successfully.";
    } catch (PDOException $e) {
        $error_message = "Error deleting game result: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Game Results</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background-color: black;
            color: white;
        }
        .topPart{
                display: flex;
        }
        .itemHeading {
            flex: 80%
        }
        .itemButton {
            flex: 20%
        }
        

    </style>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
</head>
<body>
<!-- NavBar -->
<div class="container">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark justify-content-center">
        <div id="nav-placeholder">
            <!-- The static navbar HTML will be loaded dynamically into the nav-placeholder div -->
        </div>
    </nav>
</div>
<!-- Poster Logo Header -->
<div class="poster">
    <img src="../assets/images/name.png" alt="Spring Shootout">
</div>

<div class="container">
    <h1>Edit Game Results</h1>

    <!-- Display messages -->
    <?php if ($success_message): ?>
    <div class="alert alert-success">
        <?php echo $success_message; ?>
    </div>
    <?php endif; ?>
    <?php if ($error_message): ?>
    <div class="alert alert-danger">
        <?php echo $error_message; ?>
    </div>
    <?php endif; ?>

    <!-- Dropdown to select game result -->
    <form method="POST" action="edit_results.php">
        <div class="form-group">
            <label for="game_id">Select Game:</label>
            <select id="game_id" name="game_id" class="form-control" required onchange="fetchGameData(this.value)">
                <option value="">Select a game</option>
                <?php foreach ($games as $game): ?>
                    <option value="<?php echo htmlspecialchars($game['game_id']); ?>">
                        <?php echo htmlspecialchars($game['home_team'] . " vs " . $game['away_team'] . " - " . date('M d, Y g:i A', strtotime($game['game_date'] . ' ' . $game['game_time']))); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Form to display and edit the selected game result -->
        <div class="game-result-form" style="display: none;">
            <div class="form-group">
                <label for="home_score">Home Team Score:</label>
                <input type="number" id="home_score" name="home_score" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="away_score">Away Team Score:</label>
                <input type="number" id="away_score" name="away_score" class="form-control" required>
            </div>

            <!-- Buttons to submit updated result or delete -->
            <input type="submit" name="update_result" class="btn btn-primary" value="Update Result" disabled />
            <input type="submit" name="delete_result" class="btn btn-danger" value="Delete Game Result" />
        </div>
    </form>
</div>
<script>
function fetchGameData(gameId) {
    if (!gameId) {
        $('.game-result-form').hide();
        return;
    }
    $.ajax({
        url: '/scripts/php/fetch_game_data.php', // Make sure this is the correct path to your PHP script
        type: 'GET',
        dataType: 'json', // Ensure jQuery treats the response as JSON
        data: { 'game_id': gameId },
        success: function(gameData) {
            if (gameData && !gameData.error) {
                $('#home_score').val(gameData.home_score);
                $('#away_score').val(gameData.away_score);
                $('.game-result-form').show();
            } else {
                // Handle no results or error
                alert(gameData.error || 'No results found for this game or an error occurred.');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error: ', status, error);
            console.error('Response Text: ', xhr.responseText);
        }
    });
}


    $('input[type="number"]').on('change', function() {
        $('input[name="update_result"]').prop('disabled', false);
    });

    $('input[name="delete_result"]').on('click', function(e) {
        var confirmDelete = confirm('Are you sure you want to delete this game result?');
        if (!confirmDelete) {
            e.preventDefault();
        }
    });
</script>

<script>
    $(document).ready(function() {
        $("#nav-placeholder").load("../includes/navbar.html");
        filterSchedule(); // This will load the full schedule when the page loads
    });

</script>

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the configuration file which sets the error log path
require_once '/home/lostan6/springshootout.ca/includes/config.php';
require __DIR__ . '/../scripts/php/db_connect.php'; // Include the database connection

// Check if form is submitted to enter game results
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Form submitted.");
      // Check if results for this game_id already exist
      $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM game_results WHERE game_id = :game_id");
      $checkStmt->execute([':game_id' => $game_id]);
      $resultCount = $checkStmt->fetchColumn();

    // Assuming you've validated and sanitized the input
    $game_id = $_POST['game_id'];
    $home_score = $_POST['home_score'];
    $away_score = $_POST['away_score'];
    $win = $home_score > $away_score ? 1 : 0;
    $loss = $home_score < $away_score ? 1 : 0;

    // Prepare and execute insert statement for home team
    $stmt = $pdo->prepare("INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss) VALUES (:game_id, (SELECT home_team_id FROM schedule WHERE game_id = :game_id), :points_for, :points_against, :win, :loss)");
    $stmt->execute([':game_id' => $game_id, ':points_for' => $home_score, ':points_against' => $away_score, ':win' => $win, ':loss' => $loss]);

    // Prepare and execute insert statement for away team
    $win = $home_score < $away_score ? 1 : 0;
    $loss = $home_score > $away_score ? 1 : 0;
    $stmt->execute([':game_id' => $game_id, ':points_for' => $away_score, ':points_against' => $home_score, ':win' => $win, ':loss' => $loss]);
    header('Location: menu.php');
    exit; // Make sure to ca
    $success_message = "Game result entered successfully.";
}

// Fetch games for dropdown
try {
    $stmt = $pdo->query("
        SELECT 
            s.game_id,
            ht.team_name AS home_team,
            vt.team_name AS away_team,
            s.game_time,
            s.game_date
        FROM 
            schedule s
        JOIN 
            teams ht ON ht.team_id = s.home_team_id
        JOIN 
            teams vt ON vt.team_id = s.away_team_id
        LEFT JOIN 
            game_results gr ON s.game_id = gr.game_id
        WHERE 
            gr.game_id IS NULL
        ORDER BY 
            s.game_date, s.game_time, s.game_id;
    ");
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching games: " . $e->getMessage();
}
?>
<!-- ... Rest of your HTML and Bootstrap setup ... -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Registration</title>
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

<div class="container">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark justify-content-center">
        <div id="nav-placeholder"></div>
    </nav>
 <!--  LOGO  -->
    <div class="text-center">
        <img src="../assets/images/name.png" alt="Spring Shootout">
    </div>

<!-- Game selection and score entry form -->
<div class="container">
    <h1 class="text-center mb-4">Enter Game Results</h1>
    <!-- ... Success/Error messages ... -->

    <form method="POST" action="enter_results.php">
        <div class="form-group">
            <label for="game_id" class="text-white">Select Game:</label>
            <select name="game_id" class="form-control" required>
                <option value="">Select a game</option>
                <?php foreach ($games as $game): ?>
                    <option value="<?php echo htmlspecialchars($game['game_id']); ?>">
                        <?php 
                        echo "Home Team: " . htmlspecialchars($game['home_team']) . 
                            "    ---- vs ----     Visitor Team: " . htmlspecialchars($game['away_team']) . 
                            "  ----  " . htmlspecialchars(date('M d, Y', strtotime($game['game_date']))); 
                        ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="home_score" class="text-white">Home Team Score:</label>
            <input type="number" name="home_score" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="away_score" class="text-white">Away Team Score:</label>
            <input type="number" name="away_score" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary">Submit Result</button>
    </form>
</div>

<!-- ... Your existing scripts for dynamic content loading ... -->
   <!-- jQuery and Bootstrap JS -->
   <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
   <!-- Navbar dynamic loading -->
   <script>
     $(document).ready(function() {
         $("#nav-placeholder").load("../includes/navbar.html");
     });
   </script> 
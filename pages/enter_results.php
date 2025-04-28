<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the configuration file which sets the error log path
require_once '/home/lostan6/springshootout.ca/includes/config.php';
require __DIR__ . '/../scripts/php/db_connect.php'; // Include the database connection

// Check if form is submitted to enter game results
error_log("Starting the enter_results.php script");
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $game_id = filter_input(INPUT_POST, 'game_id', FILTER_VALIDATE_INT);
  $home_score = filter_input(INPUT_POST, 'home_score', FILTER_VALIDATE_INT);
  $away_score = filter_input(INPUT_POST, 'away_score', FILTER_VALIDATE_INT);

  if (!$game_id || $home_score === false || $away_score === false) {
      $error_message = "Invalid input. Please ensure all fields are filled out correctly.";
  } else {
      try {
          $pdo->beginTransaction();

          // Fetch team IDs based on the game ID
          $teamStmt = $pdo->prepare("SELECT home_team_id, away_team_id FROM schedule WHERE game_id = :game_id");
          $teamStmt->execute([':game_id' => $game_id]);
          $teamResult = $teamStmt->fetch(PDO::FETCH_ASSOC);

          if ($teamResult) {
              $home_team_id = $teamResult['home_team_id'];
              $away_team_id = $teamResult['away_team_id'];

              // Determine the winner and loser
              $home_win = $home_score > $away_score ? 1 : 0;
              $home_loss = $home_score < $away_score ? 1 : 0;
              $away_win = $home_score < $away_score ? 1 : 0;
              $away_loss = $home_score > $away_score ? 1 : 0;

              // Insert statement for home team
              $stmt = $pdo->prepare("INSERT INTO game_results (game_id, team_id, points_for, points_against, win, loss) VALUES (:game_id, :team_id, :points_for, :points_against, :win, :loss)");
              $stmt->execute([
                  ':game_id' => $game_id,
                  ':team_id' => $home_team_id,
                  ':points_for' => $home_score,
                  ':points_against' => $away_score,
                  ':win' => $home_win,
                  ':loss' => $home_loss
              ]);

              // Insert statement for away team
              $stmt->execute([
                  ':game_id' => $game_id,
                  ':team_id' => $away_team_id,
                  ':points_for' => $away_score,
                  ':points_against' => $home_score,
                  ':win' => $away_win,
                  ':loss' => $away_loss
              ]);

              $pdo->commit();
              $success_message = "Game result entered successfully.";
              header('Location: menu.php');
              exit;
          } else {
              throw new Exception("No teams found for the provided game ID.");
          }
      } catch (PDOException $e) {
          $pdo->rollBack();
          $error_message = "Error entering game results: " . $e->getMessage();
      } catch (Exception $e) {
          $pdo->rollBack();
          $error_message = $e->getMessage();

      }
  }
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


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enter Results</title>
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
            <select id="game_id" name="game_id" class="form-control" required>
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
            <input type="number" id="home_score" name="home_score" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="away_score" class="text-white">Away Team Score:</label>
            <input type="number" id="away_score" name="away_score" class="form-control" required>
        </div>

        <input type="submit" class="btn btn-primary" value="Submit Result" onclick="disableButton(this)" />
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

   <!-- Script to disable the submit button -->
  <script>
    function disableButton(btn) {
        btn.disabled = true;
        btn.value = 'Submitting...'; // Optional: Change button text to indicate processing
        btn.form.submit();
    }
  </script>
</body>
</html>
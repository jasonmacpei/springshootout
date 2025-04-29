<?php
require __DIR__ . '/../scripts/php/db_connect.php'; // Include the database connection

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Assuming you've validated and sanitized the input
    $home_team_id = !empty($_POST['home_team_id']) ? $_POST['home_team_id'] : NULL;
    $away_team_id = !empty($_POST['away_team_id']) ? $_POST['away_team_id'] : NULL;
    $game_time = $_POST['game_time'];
    $game_date = $_POST['game_date'];
    $gym = $_POST['gym'];
    $game_type = $_POST['game_type'];

    $query = "INSERT INTO schedule (home_team_id, away_team_id, game_time, game_date, gym, game_type) VALUES (:home_team_id, :away_team_id, :game_time, :game_date, :gym, :game_type)";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':home_team_id', $home_team_id);
        $stmt->bindParam(':away_team_id', $away_team_id);
        $stmt->bindParam(':game_time', $game_time);
        $stmt->bindParam(':game_date', $game_date);
        $stmt->bindParam(':gym', $gym);
        $stmt->bindParam(':game_type', $game_type);
        $stmt->execute();
        $success_message = "Schedule created successfully.";
    } catch (PDOException $e) {
        $error_message = "Error creating schedule: " . $e->getMessage();
    }
}

// Fetch teams from registrations table for select options
try {
    $stmt = $pdo->query("
    select 
        r.team_id
        , t.team_name
    from registrations r 
    left outer join teams t on r.team_id = t.team_id
        where year = 2025 
        and status = 1
    order by registration_id;
        ");
    $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching teams: " . $e->getMessage();
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
    </style>
</head>
<body>

<!-- Nav Bar below -->
<div class="container">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark justify-content-center">
  <div id="nav-placeholder"></div>

</div>
</div>

    <!-- Logo -->
    <div class="text-center">
        <img src="../assets/images/name.png" alt="Spring Shootout">
    </div>

<!-- Form container -->
<div class="container">
    <h1 class="text-center mb-4">Create Game Schedule</h1>
    <?php if (!empty($success_message)) echo "<div class=\"alert alert-success\" role=\"alert\">$success_message</div>"; ?>
    <?php if (!empty($error_message)) echo "<div class=\"alert alert-danger\" role=\"alert\">$error_message</div>"; ?>


<!-- Schedule form -->
<form method="POST" action="make_schedule.php">
    <div class="form-row">

        <div class="form-group col-md-6">
            <label for="home_team_id" class="text-white">Home Team:</label>
            <select name="home_team_id" class="form-control">
                <option value="">TBD</option>
                <?php foreach ($teams as $team): ?>
                    <option value="<?php echo htmlspecialchars($team['team_id']); ?>">
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
                    <option value="<?php echo htmlspecialchars($team['team_id']); ?>">
                        <?php echo htmlspecialchars($team['team_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group col-md-6">
            <label for="game_time" class="text-white">Game Time:</label>
            <input type="time" name="game_time" class="form-control" required>
        </div>

        <div class="form-group col-md-6">
            <label for="game_date" class="text-white">Game Date:</label>
            <input type="date" id="game_date" name="game_date" class="form-control" required value="2025-04-28">
        </div>

    </div>

    <div class="form-group">
      <label for="gym" class="text-white">Gym:</label>
        <select name="gym" class="form-control" required>
            <option value="" disabled selected>Please choose a gym</option>
            <option value="Town Hall">Town Hall</option>
            <option value="Donagh">Donagh</option>
            <option value="Stonepark">Stonepark</option>
            <option value="Rural">Rural</option>
            <option value="Glen Stewart">Glen Stewart</option>
            <option value="Colonel Grey">Colonel Grey</option>
            <option value="UPEI">UPEI</option>
        </select>
    </div>


    <div class="form-group">
        <label for="game_type" class="text-white">Game Type:</label>
        <select name="game_type" class="form-control" required>
            <option value="" disabled selected>Please choose a game type</option>
            <option value="u11">u11 Pool</option>
            <option value="u12">u12 Pool</option>
            <option value="u13">u13 Pool</option>            
            <option value="u10 A1 vs A2">u10 A1 vs A2</option>
            <option value="u10 B1 vs B2">u10 B1 vs B2</option>
            <option value="u10 A3 vs B3">u10 A3 vs B3</option>
            <option value="u10 Championship<">u10 Championship</option>
            <option value="u12 C1 vs D2">u12 C1 vs D2</option>
            <option value="u12 C2 vs D1">u12 C2 vs D1</option>
            <option value="u12 C3 vs D3">u12 C3 vs D3</option>
            <option value="u12 C4 vs D4">u12 C4 vs D4</option>
            <option value="u12 Championship">u12 Championship</option>            
        </select>
    </div>

    <div class="form-group">
        <button type="submit" class="btn btn-primary">Create Game</button>
        <a href="http://www.springshootout.ca/pages/schedule.php" class="btn btn-secondary">Show Schedule</a>
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
        });
    </script>
</body>
</html>


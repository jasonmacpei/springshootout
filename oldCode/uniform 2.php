<?php
    require __DIR__ . '/../scripts/php/db_connect.php'; // Include the database connection
    require_once '/home/lostan6/springshootout.ca/includes/config.php'; // Include the error log configuration
    error_log("Run uniform.php was run at " . date("Y-m-d H:i:s"));

    $current_uniform = []; // Initialize to prevent undefined variable errors

    // Check if form is submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Assuming you've validated and sanitized the input
        $team_id = $_POST['team_id'];
        $home_color = $_POST['home_color'];
        $away_color = $_POST['away_color'];

        // First check if the colors already exist for the team
        $uniformQuery = "SELECT home_color, away_color FROM team_uniforms WHERE team_id = :team_id";
        $uniformStmt = $pdo->prepare($uniformQuery);
        $uniformStmt->execute(['team_id' => $team_id]);
        $current_uniform = $uniformStmt->fetch(PDO::FETCH_ASSOC);
        error_log('Current Uniform Data Dump: ' . print_r($current_uniform, true)); // Correct way to log array

        // If colors exist, perform an UPDATE; otherwise, perform an INSERT
        if ($current_uniform) {
            $query = "UPDATE team_uniforms SET home_color = :home_color, away_color = :away_color WHERE team_id = :team_id";
        } else {
            $query = "INSERT INTO team_uniforms (team_id, home_color, away_color) VALUES (:team_id, :home_color, :away_color)";
        }

        try {
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':team_id', $team_id);
            $stmt->bindParam(':home_color', $home_color);
            $stmt->bindParam(':away_color', $away_color);
            $stmt->execute();
            $success_message = $current_uniform ? "Team Colors Updated." : "Team Colors Entered.";
        } catch (PDOException $e) {
            $error_message = "Error entering/updating colors: " . $e->getMessage();
        }
    }

    // Fetch teams from registrations table for select options
    try {
        $stmt = $pdo->query("
            select 
                t.team_id
                , t.team_name
                from registrations r
                left join teams t on r.team_id = t.team_id
                where r.year = 2024
                and r.status=1
            ");
        $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "Error fetching teams: " . $e->getMessage();
    }

    // Ensure the current uniform data is fetched when the page is first loaded or when a team is selected
    if (!empty($_POST['team_id'])) {
        // Query to get the current uniform colors
        $uniformQuery = "SELECT home_color, away_color FROM team_uniforms WHERE team_id = :team_id";
        $uniformStmt = $pdo->prepare($uniformQuery);
        $uniformStmt->execute(['team_id' => $_POST['team_id']]);
        $current_uniform = $uniformStmt->fetch(PDO::FETCH_ASSOC);
    }
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enter Uniform Colors</title>
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
  <h1 class="text-center mb-4">Enter Uniform Colors</h1>
  <?php if (!empty($success_message)) echo "<div class=\"alert alert-success\" role=\"alert\">$success_message</div>"; ?>
  <?php if (!empty($error_message)) echo "<div class=\"alert alert-danger\" role=\"alert\">$error_message</div>"; ?>

<!-- Schedule form -->
<form method="POST" action="uniform.php">
    <div class="form-row">
        <div class="form-group col-md-4">
            <label for="team_id" class="text-white">Team:</label>
            <select name="team_id" class="form-control" onchange="fetchUniformColors(this.value)" required>
                <option value="">Select a Team</option>
                <?php foreach ($teams as $team): ?>
                    <option value="<?php echo htmlspecialchars($team['team_id']); ?>"
                        <?php echo (isset($current_uniform) && $team['team_id'] == $_POST['team_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($team['team_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group col-md-4">
            <label for="home_color" class="text-white">Home Uniform Color:</label>
            <input type="text" name="home_color" class="form-control" value="<?php echo htmlspecialchars($current_uniform['home_color'] ?? ''); ?>" required>
        </div>

        <div class="form-group col-md-4">
            <label for="away_color" class="text-white">Away Uniform Color:</label>
            <input type="text" name="away_color" class="form-control" value="<?php echo htmlspecialchars($current_uniform['away_color'] ?? ''); ?>" required>
        </div>
    </div>
    <button type="submit" class="btn btn-primary">Enter Uniform Color</button>
</form>

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
    <script>
            function fetchUniformColors(teamId) {
                if (teamId) {
                    $.ajax({
                        type: "POST",
                        url: "/scripts/php/fetch_uniform_colors.php",
                        data: { team_id: teamId },
                        success: function(response) {
                            if (response) {
                                $("input[name='home_color']").val(response.home_color);
                                $("input[name='away_color']").val(response.away_color);
                            } else {
                                $("input[name='home_color']").val('');
                                $("input[name='away_color']").val('');
                            }
                        }
                    });
                } else {
                    $("input[name='home_color']").val('');
                    $("input[name='away_color']").val('');
                }
            }
</script>

</body>
</html>
<?php
require __DIR__ . '/../scripts/php/db_connect.php'; // Include the database connection

// Query the schedule from the database
$query = "SELECT 
        s.game_date
        , s.game_time
        , h.team_name AS home_team_name
        , s.home_uniform
        , a.team_name AS away_team_name
        , s.away_uniform
        , s.gym
        , s.game_type
      FROM schedule s
      JOIN teams h ON s.home_team_id = h.team_id
      JOIN teams a ON s.away_team_id = a.team_id
      ORDER BY s.game_date, s.game_time;";
$result = $pdo->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom styles if needed -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .white-text {
            color: white;
        }
        .table-white tbody td, .table-white tbody th,
        .table-white thead th, .table-white tfoot td {
            color: white;
        }
    </style>
</head>
<body>

<div class="container">
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark justify-content-center">
      <div id="nav-placeholder"></div>
    
      <!-- The static navbar HTML is removed because it will be loaded dynamically into the nav-placeholder div -->
    </div>
    </div>
  <!-- Content of the page -->
  <div class="poster">
    <img src="../assets/images/name.png">
  </div>
  <div style="padding-top: 20px;">
    <hr>
  </div>
  <body>
  <div class="container my-3">
    <div class="row">
      <div class="col-md-6">
        <h3 class="text-center">u10 Division</h3>
        <table class="table table-bordered white_text">
        <thead class="thead-dark">
            <tr>
              <th scope="col">Pool A</th>
              <th scope="col">Pool B</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Island Aces MacDonald</td>
              <td>Bedford Elite 1</td>
            </tr>
            <tr>
              <td>Island Aces Walker</td>
              <td>Bedford Elite 2</td>
            </tr>
            <tr>
              <td>UNB Jr Reds</td>
              <td>East Hants Tigers</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="col-md-6">
        <h3 class="text-center">u12 Division</h3>
        <table class="table table-bordered">
          <thead>
            <tr>
              <th scope="col">Pool C</th>
              <th scope="col">Pool D</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Bedford Elite 1</td>
              <td>Bedford Elite 2</td>
            </tr>
            <tr>
              <td>Evolution Basketball</td>
              <td>Fury</td>
            </tr>
            <tr>
              <td>NXT Level</td>
              <td>Sumerside Sting</td>
            </tr>
            <tr>
              <td>Surge Select</td>
              <td>Woodstock Thunder</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

    <div class="container">
        <h1 class="mt-4">Schedule</h1>
        <a href="download_schedule.php" class="btn btn-primary mb-3">Download as PDF</a>

        <table class="table table-striped table-white">
            <thead class="thead-dark">
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Home Team</th>
                    <th>Home Uniform</th>
                    <th>Away Team</th>
                    <th>Away Uniform</th>
                    <th>Gym</th>
                    <th>Game Type</th>
                </tr>
            </thead>
            <tbody class="white-text">
                <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['game_date']) ?></td>
                        <td><?= htmlspecialchars($row['game_time']) ?></td>
                        <td><?= htmlspecialchars($row['home_team_name']) ?></td>
                        <td><?= htmlspecialchars($row['home_uniform']) ?></td>
                        <td><?= htmlspecialchars($row['away_team_name']) ?></td>
                        <td><?= htmlspecialchars($row['away_uniform']) ?></td>
                        <td><?= htmlspecialchars($row['gym']) ?></td>
                        <td><?= htmlspecialchars($row['game_type']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

  <!-- jQuery for dynamic content loading -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <!-- Bootstrap JS for Bootstrap components functionality -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  <!-- Script to dynamically load the navbar -->
  <script>
    $(document).ready(function() {
        $("#nav-placeholder").load("../includes/navbar.html");
    });
  </script>
</body>
</html>


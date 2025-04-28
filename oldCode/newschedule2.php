<?php
// Only start the session and output buffering if it's an AJAX request
$isAjaxRequest = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($isAjaxRequest) {
    header('Content-Type: application/json');
    
    // Include necessary configuration and connection files
    require_once '/home/lostan6/springshootout.ca/includes/config.php';
    require __DIR__ . '/../scripts/php/db_connect.php';
    
    // Sanitize and validate gym filter input
    $gymFilter = filter_input(INPUT_GET, 'gym', FILTER_SANITIZE_STRING);
    $gymFilter = $gymFilter !== '' ? $gymFilter : null;

    // Prepare the query
    $query = "SELECT s.game_date, s.game_time, h.team_name AS home_team_name, s.home_uniform,
    a.team_name AS away_team_name, s.away_uniform, s.gym, s.game_type
    FROM schedule s
    LEFT JOIN teams h ON s.home_team_id = h.team_id
    LEFT JOIN teams a ON s.away_team_id = a.team_id";
    
    if ($gymFilter) {
        $query .= " WHERE s.gym = :gymFilter";
    }
    
    $query .= " ORDER BY s.game_date, s.game_time;";
    $stmt = $pdo->prepare($query);

    if ($gymFilter) {
        $stmt->bindParam(':gymFilter', $gymFilter);
    }
    
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Start buffering the output
    ob_start();
    foreach ($rows as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['game_date']) . "</td>";
        echo "<td>" . htmlspecialchars($row['game_time']) . "</td>";
        echo "<td>" . ($row['home_team_name'] ? htmlspecialchars($row['home_team_name']) : 'TBD') . "</td>";
        echo "<td>" . ($row['away_team_name'] ? htmlspecialchars($row['away_team_name']) : 'TBD') . "</td>";
        echo "<td>" . htmlspecialchars($row['gym']) . "</td>";
        echo "<td>" . htmlspecialchars($row['game_type']) . "</td>";
        echo "</tr>";
    }
    // Get the buffer content
    $htmlOutput = ob_get_clean();
    
    // Send back the HTML as a JSON response
    echo json_encode(array('html' => $htmlOutput));
    exit(); // Important: Stop the script after outputting the AJAX response
} else {
    // Start the session for a normal request
    session_start();
}

// ... Rest of your HTML content below
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
        .table-responsive {
            width: 100%;
            overflow-x: auto; /* Ensure that scrolling is possible */
            -webkit-overflow-scrolling: touch; /* For smooth scrolling on iOS */
        }

        .table td, .table th {
            padding: .5rem;
            font-size: .85rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }



@media (max-width: 768px) {
    .table td, .table th {
      padding: .3rem; /* Adjust padding */
        font-size: 0.7rem; /* Adjust font size */
        max-width: 100px; /* Prevents cells from being too wide */
    }
}

/* Phones */
@media (max-width: 480px) {
    .table td, .table th {
        padding: .2rem; /* Smaller padding */
        font-size: 0.6rem; /* Smaller font size */
        max-width: 80px; /* Even more narrow cells */
    }
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

<!-- u10 and u12 Division tables, each in their own .table-responsive if necessary -->
<div class="container my-3">
    <div class="row">
        <div class="col-md-6">

          <h3 class="text-center">u10 Division</h3>
          <table class="table table-bordered white-text">
          <thead class="thead-dark">
              <tr>
                <th scope="col">Pool A</th>
                <th scope="col">Pool B</th>
              </tr>
            </thead>
            <tbody class="white-text">
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
          <table class="table table-bordered white-text">
          <thead class="thead-dark">
              <tr>
                <th scope="col">Pool C</th>
                <th scope="col">Pool D</th>
              </tr>
            </thead>
            <tbody class="white-text">
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

        <select id="gymFilter" class="form-control mb-3" onchange="filterSchedule()">
            <option value="">All Gyms</option>
            <option value="Glen Stewart Elementary">Glen Stewart Elementary</option>
            <option value="Stratford Elementary">Stratford Elementary</option>
            <option value="Spring Park Elementary">Spring Park Elementary</option>
            <option value="Stonepark Jr High">Stonepark Jr High</option>
            <option value="Birchwood Jr High">Birchwood Jr High</option>
        </select>

        <a href="download_schedule.php" class="btn btn-primary mb-3">Download as PDF</a>


        <div class="table-responsive"> 
          <table class="table table-striped table-white">
              <thead class="thead-dark">
                  <tr>
                      <th>Date</th>
                      <th>Time</th>
                      <th>Home Team</th>
                      <th>Away Team</th>
                      <th>Gym</th>
                      <th>Game Type</th>
                  </tr>
              </thead>


              <tbody id="scheduleTableBody">
                  <!-- This content will be replaced by AJAX -->
              </tbody>

                    </table>
                    </div>
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

<script>
function filterSchedule() {
    var gym = document.getElementById('gymFilter').value;
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'schedule.php?gym=' + encodeURIComponent(gym), true);
    xhr.onload = function () {
        if (xhr.status >= 200 && xhr.status < 300) {
            try {
                var response = JSON.parse(xhr.responseText);
                document.getElementById('scheduleTableBody').innerHTML = response.html;
            } catch (e) {
                console.error("Error parsing JSON:", e);
                alert('Error parsing response. Please check the console for more information.');
            }
        } else {
            console.error(xhr.statusText);
            alert('HTTP Error: ' + xhr.statusText);
        }
    };
    xhr.onerror = function () {
        console.error("Network error occurred");
        alert('Network Error. Please check your connection.');
    };
    xhr.send();
}
</script>

</body>
</html>
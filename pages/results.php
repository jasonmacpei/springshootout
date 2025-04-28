<?php
// Include the database connection setup file
require_once '/home/lostan6/springshootout.ca/includes/config.php';
require __DIR__ . '/../scripts/php/db_connect.php';
error_log("Starting the results.php script.");

// Initialize the gamesByDate array
$gamesByDate = [];

try {
    // Assuming $pdo is your PDO instance from db_connect.php
    $sql = "SELECT
    g.game_date,
    g.game_time,
    g.gym,
    ht.team_name AS home_team_name,
    vt.team_name AS away_team_name,
    hgr.home_team_score,
    agr.away_team_score,
    CONCAT(r.division, ' - Pool ', p.pool_name) AS division_section
FROM
    schedule g
JOIN teams ht ON g.home_team_id = ht.team_id
JOIN registrations r on r.team_id = g.home_team_id
JOIN teams vt ON g.away_team_id = vt.team_id
JOIN (
    SELECT game_id, team_id, points_for AS home_team_score
    FROM game_results
    WHERE team_id IN (SELECT home_team_id FROM schedule)
) hgr ON g.game_id = hgr.game_id AND g.home_team_id = hgr.team_id
JOIN (
    SELECT game_id, team_id, points_for AS away_team_score
    FROM game_results
    WHERE team_id IN (SELECT away_team_id FROM schedule)
) agr ON g.game_id = agr.game_id AND g.away_team_id = agr.team_id
JOIN team_pools tp ON ht.team_id = tp.team_id
JOIN pools p ON tp.pool_id = p.pool_id
WHERE  
    g.game_id IS NOT NULL
ORDER BY
    g.game_date ASC, g.game_time ASC;";

    // Execute the query and fetch the results
    $stmt = $pdo->query($sql);

    // Fetch all the results into an array
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as $row) {
      // Use the game date as the key for the gamesByDate array
      $date = date('l, F d, Y', strtotime($row['game_date']));
      // Append the game details to the array for this date
      $gamesByDate[$date][] = $row;
  }
    
    // Now, loop through the results and build the HTML
    // while ($row = $stmt->fetch()) {
    //     echo "<div class=\"results-card\">";
    //     echo "<div class=\"results-header\">";
    //     echo htmlentities(date('l, F d, Y', strtotime($row['game_date'])));
    //     echo "</div>";
    //     echo "<div class=\"game-detail\">";
    //     echo "<div class=\"game-time-location\">";
    //     echo htmlentities($row['game_time'] . ' @ ' . $row['gym']);
    //     echo "</div>";
    //     echo "<div class=\"team-score-container\">";
    //     echo "<div class=\"team-name\" style=\"color: orange;\">" . htmlentities($row['home_team_name']) . "</div>";
    //     echo "<div class=\"score\" style=\"color: black;\">" . htmlentities($row['home_team_score']) . "</div>";
    //     echo "</div>";
    //     echo "<div class=\"team-score-container\">";
    //     echo "<div class=\"team-name\" style=\"color: orange;\">" . htmlentities($row['away_team_name']) . "</div>";
    //     echo "<div class=\"score\" style=\"color: black;\">" . htmlentities($row['away_team_score']) . "</div>";
    //     echo "</div>";
    //     echo "<div class=\"division-final\">";
    //     echo "<div class=\"division\" style=\"color: grey;\">" . htmlentities($row['division_section']) . "</div>";
    //     echo "<div class=\"final-status\">Final</div>";
    //     echo "</div>";
    //     echo "</div>";
    //     echo "</div>";
    // }
    
} catch (\PDOException $e) {
    error_log("Database error: " . $e->getMessage()); // Write errors to the error_log
    // Optionally, show a user-friendly message
    echo "<p>An error occurred while fetching the results. Please try again later.</p>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Results</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom styles if needed -->
    <link rel="stylesheet" href="../assets/css/style.css">
  <style>
<style>

.results-container {
    display: flex;
    flex-wrap: wrap; /* Allows cards to wrap onto the next line */
    justify-content: flex-start; /* Align items to the start of the container */
    margin: 0 auto; /* Center the container */
    padding: 10px; /* Padding inside the container */
}

.results-card {
    background-color: white;
    border: 1px solid #ccc;
    padding: 10px;
    box-sizing: border-box;
    width: 400px; /* Fixed width */
    margin: 10px; /* Consistent margin around cards */
    flex-shrink: 0; /* Prevents the flex item from shrinking */
}

.results-date {
    width: 300px; /* Initial width for large screens */
    background-color: lightgray; /* White background for the outer box */
    border: 8px solid white; /* Sets a 20px wide solid white border */
    color: black; /* Black font color */
    font-size: 1.25em; /* 2em font size */
    padding: 5px; /* Padding around the text */
    box-sizing: border-box; /* Includes padding in width calculation */
    margin-bottom: 20px; /* Space below each date box */
    float: left; /* Align the box to the left */
    position: relative; /* Position relative for the inner positioning */
    box-shadow: 0 2px 5px rgba(0,0,0,0.1); /* Subtle shadow for depth */
    clear: both; /* Clears the floating effect of any preceding elements */
}
*/

.results-date::after {
    content: ""; /* Required for pseudo-elements */
    background-color: grey; /* Grey background for the inner box */
    position: absolute; /* Absolute position inside the parent */
    top: 5px; /* Slightly lower than the top edge */
    bottom: 5px; /* Slightly above the bottom edge */
    left: 5px; /* Slightly right of the left edge */
    right: 5px; /* Slightly left of the right edge */
    z-index: -1; /* Place it behind the content */
}

.results-header {
    background-color: #333;
    color: white;
    padding: 10px;
    font-size: 1.2rem;
    width: 100%;
    text-align: center;
}

.game-detail {
    padding: 10px;
}

.game-time-location {
    font-weight: bold;
    margin-bottom: 10px; /* Space between game time/location and team names */
    
}

.team-score-container {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
}

.team-name {
    flex-grow: 1;
    text-align: left;
    color: orange;
}

.score {
    flex-shrink: 0;
    text-align: right;
    color: black;
}

.division-final {
    background-color: lightgray;
    display: flex;
    justify-content: space-between;
    padding: 5px;
}

.division {
    flex-grow: 1;
    text-align: left;
    color: grey;
}

.final-status {
    flex-shrink: 0;
    text-align: right;
    color: black;
}
@media (max-width: 850px) {
    .results-card {
        width: 400; /* Full width for smaller screens */
        margin: 10px 0; /* Adjust margin for vertical stacking */
    }
}
/* Media queries for responsive adjustments */
@media (max-width: 768px) {
    .results-card {
        padding: 5px;
    }
    
    .results-header {
        font-size: 1rem;
    }
    
    .game-time-location, .division, .final-status {
        font-size: 0.9rem;
    }
    
    .team-name, .score {
        font-size: 0.85rem;
    }
    .results-date {
    width: 250px; /* Initial width for large screens */
    font-size: 1.0em; /* 2em font size */
    }
}

@media (max-width: 576px) {
    .results-header {
        font-size: 0.9rem;
    }
    
    .game-time-location, .division, .final-status, .team-name, .score {
        font-size: 0.8rem;
    }
    .results-date {
        width: 200px; /* Initial width for large screens */
    font-size: 0.75em; /* 2em font size */
    }
}

</style>


</style>
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

<h1>Results</h1>
<div class="results-container">
    <?php foreach ($gamesByDate as $date => $games): ?>
        <div class="results-date">
        <?php echo htmlentities($date); ?>
    </div>
    <div style="clear: both;"></div> <!-- Clears the floating effect -->
    <?php foreach ($games as $game): ?>
        <div class="results-card">
            <div class="game-time-location">
                <?php
                // Convert and format the game time
                $formattedTime = date('g:i A', strtotime($game['game_time']));
                echo htmlentities($formattedTime . ' @ ' . $game['gym']);
                ?>
            </div>
                <div class="game-detail">
                    <div class="team-score-container">
                        <div class="team-name" style="color: orange;"><?php echo htmlentities($game['home_team_name']); ?></div>
                        <div class="score" style="color: black;"><?php echo htmlentities($game['home_team_score']); ?></div>
                    </div>
                    <div class="team-score-container">
                        <div class="team-name" style="color: orange;"><?php echo htmlentities($game['away_team_name']); ?></div>
                        <div class="score" style="color: black;"><?php echo htmlentities($game['away_team_score']); ?></div>
                    </div>
                    <div class="division-final">
                        <div class="division" style="color: grey;"><?php echo htmlentities($game['division_section']); ?></div>
                        <div class="final-status">Final</div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
</div>

  <!-- jQuery for dynamic content loading -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <!-- Bootstrap JS for Bootstrap components functionality -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  <!-- Script to dynamically load the navbar -->
  <script>
    $(document).ready(function() {
        $("#nav-placeholder").load("../includes/navbar.html");
        filterSchedule(); // This will load the full schedule when the page loads
    });
  </script>

</body>
</html>

<?php
// Include the database connection
require __DIR__ . '/../scripts/php/db_connect.php';

function getStandings($pdo, $poolName) {
    $stmt = $pdo->prepare("
        SELECT 
            p.pool_name,
            t.team_name,
            COALESCE(COUNT(gr.game_id), 0) AS games_played,
            COALESCE(SUM(gr.win), 0) AS wins,
            COALESCE(SUM(gr.loss), 0) AS losses,
            COALESCE(SUM(gr.points_for), 0) AS points_for,
            COALESCE(SUM(gr.points_against), 0) AS points_against,
            COALESCE(SUM(gr.points_for), 0) - COALESCE(SUM(gr.points_against), 0) AS plus_minus
        FROM 
            registrations r
        JOIN 
            teams t ON r.team_id = t.team_id
        LEFT JOIN 
            team_pools tp ON t.team_id = tp.team_id
        LEFT JOIN 
            pools p ON tp.pool_id = p.pool_id
        LEFT JOIN 
            game_results gr ON t.team_id = gr.team_id
        WHERE 
            r.year = 2024 AND r.status = 1 AND p.pool_name = :poolName
        GROUP BY 
            p.pool_name, t.team_name
        ORDER BY 
            wins DESC, plus_minus DESC, points_for DESC;
    ");
    $stmt->execute(['poolName' => $poolName]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$poolAStandings = getStandings($pdo, 'A');
$poolBStandings = getStandings($pdo, 'B');
$poolCStandings = getStandings($pdo, 'C');
$poolDStandings = getStandings($pdo, 'D');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Standings Page</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
    body {
        background-color: black;
        color: white;
    }
    .container-fluid {
        padding-top: 30px;
    }
    .pool-table-container {
        overflow-x: auto;
    }
    .pool-table {
        width: 100%;
        margin-bottom: 40px;
        table-layout: fixed;
    }
    .pool-table th {
        padding: 0.5em;
        text-align: center;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .pool-table td {
        padding: 0.5em;
        text-align: center;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .pool-table td.team-name {
        text-align: left; /* Left-align text for team name cells */
        white-space: nowrap; /* Ensure the team name does not wrap */
    }
    .pool-table .team-name {
      text-align: left; /* Align team names to the left */
        width: 230px;
        min-width: 160px; /* Minimum width for team name column on smaller screens */
        white-space: nowrap; /* Ensure the team name does not wrap */
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .pool-heading {
        background-color: #000;
        color: #fff;
        font-size: 1.2em;
        padding: 0.5em;
        margin-top: 1em;
        text-align: center;
        display: block;
    }
    /* Adjusting the team name column width and table font sizes on smaller screens */
    @media (max-width: 1200px) {
        .pool-table th, .pool-table td {
            font-size: 0.9em; /* Adjusting font size as the screen gets smaller */
        }
        .pool-table .team-name {
            width: 200px; /* Adjust width for medium screens */
        }
    }
    @media (max-width: 992px) {
        .pool-table .team-name {
            width: 180px; /* Adjust width for small screens */
        }
    }
    @media (max-width: 768px) {
        .pool-table th, .pool-table td {
            font-size: 0.8em; /* Smaller font size for very small screens */
        }
    }
    @media (max-width: 576px) {
        .pool-table th, .pool-table td {
            font-size: 0.75em; /* Smallest font size for the smallest screens */
        }
        .pool-table .team-name {
            width: 160px; /* Enforce the smallest width for team name column */
        }
        .pool-heading {
            font-size: 1em; /* Smaller font size for pool headings on the smallest screens */
        }
    }
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

<div class="row">
    <div class="col-lg-6 col-md-12">
        <div class="pool-table-container">
            <h3 class="pool-heading">Pool A</h3>
            <?php echo createStandingsTable($poolAStandings); ?>
        </div>
    </div>
    <div class="col-lg-6 col-md-12">
        <div class="pool-table-container">
            <h3 class="pool-heading">Pool B</h3>
            <?php echo createStandingsTable($poolBStandings); ?>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-6 col-md-12">
        <div class="pool-table-container">
            <h3 class="pool-heading">Pool C</h3>
            <?php echo createStandingsTable($poolCStandings); ?>
        </div>
    </div>
    <div class="col-lg-6 col-md-12">
        <div class="pool-table-container">
            <h3 class="pool-heading">Pool D</h3>
            <?php echo createStandingsTable($poolDStandings); ?>
        </div>
    </div>
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

</body>
</html>

<?php
// Helper function to create standings tables
function createStandingsTable($standings) {
    $tableHTML = '<table class="table table-dark pool-table">';
    $tableHTML .= '<thead>
                        <tr>
                            <th class="team-name">Team Name</th>
                            <th class="stat">G</th>
                            <th class="stat">W</th>
                            <th class="stat">L</th>
                            <th class="stat">PF</th>
                            <th class="stat">PA</th>
                            <th class="stat">+/-</th>
                        </tr>
                    </thead>
                    <tbody>';

    foreach ($standings as $row) {
        $tableHTML .= '<tr>
                            <td class="team-name">'.htmlspecialchars($row['team_name']).'</td>
                            <td>'.$row['games_played'].'</td>
                            <td>'.$row['wins'].'</td>
                            <td>'.$row['losses'].'</td>
                            <td>'.$row['points_for'].'</td>
                            <td>'.$row['points_against'].'</td>
                            <td>'.$row['plus_minus'].'</td>
                        </tr>';
    }

    $tableHTML .= '</tbody></table>';
    return $tableHTML;
}
?>

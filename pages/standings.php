<?php
// Include the database connection
require __DIR__ . '/../scripts/php/db_connect.php';

// Function to get standings for a specific pool
function getStandingsByPoolId($pdo, $poolId) {
    $stmt = $pdo->prepare("
        SELECT 
            p.pool_name,
            t.team_id,
            t.team_name,
            COALESCE(COUNT(DISTINCT gr.game_id), 0) AS games_played,
            COALESCE(SUM(gr.win), 0) AS wins,
            COALESCE(SUM(gr.loss), 0) AS losses,
            COALESCE(SUM(gr.points_for), 0) AS points_for,
            COALESCE(SUM(gr.points_against), 0) AS points_against,
            COALESCE(SUM(gr.points_for), 0) - COALESCE(SUM(gr.points_against), 0) AS plus_minus
        FROM 
            teams t
        JOIN 
            team_pools tp ON t.team_id = tp.team_id
        JOIN 
            pools p ON tp.pool_id = p.pool_id
        JOIN 
            registrations r ON t.team_id = r.team_id
        LEFT JOIN 
            game_results gr ON t.team_id = gr.team_id
        LEFT JOIN
            schedule s ON gr.game_id = s.game_id AND s.game_category = 'pool'
        WHERE 
            r.year = 2025 AND r.status = 1 AND p.pool_id = :poolId
        GROUP BY 
            p.pool_name, t.team_id, t.team_name
        ORDER BY 
            wins DESC, plus_minus DESC, points_for DESC
    ");
    $stmt->execute([':poolId' => $poolId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to create HTML for a standings table
function createStandingsTable($standings) {
    if (empty($standings)) {
        return '<div class="alert alert-info">No teams assigned to this pool yet.</div>';
    }
    
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

// Get all divisions with active teams
try {
    $divisionsStmt = $pdo->query("
        SELECT DISTINCT r.division
        FROM registrations r
        JOIN teams t ON r.team_id = t.team_id
        JOIN team_pools tp ON t.team_id = tp.team_id
        WHERE r.year = 2025 AND r.status = 1
        ORDER BY r.division
    ");
    $divisions = $divisionsStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $error = "Error fetching divisions: " . $e->getMessage();
    $divisions = [];
}

// Structure to hold all standings data
$divisionPoolsData = [];

// For each division, get the pools
foreach ($divisions as $division) {
    try {
        $poolsStmt = $pdo->prepare("
            SELECT DISTINCT p.pool_id, p.pool_name
            FROM pools p
            JOIN team_pools tp ON p.pool_id = tp.pool_id
            JOIN teams t ON tp.team_id = t.team_id
            JOIN registrations r ON t.team_id = r.team_id
            WHERE r.division = :division AND r.year = 2025 AND r.status = 1
            ORDER BY p.pool_name
        ");
        $poolsStmt->execute([':division' => $division]);
        $pools = $poolsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get standings for each pool in the division
        $poolsData = [];
        foreach ($pools as $pool) {
            $poolsData[] = [
                'pool_id' => $pool['pool_id'],
                'pool_name' => $pool['pool_name'],
                'standings' => getStandingsByPoolId($pdo, $pool['pool_id'])
            ];
        }
        
        $divisionPoolsData[$division] = $poolsData;
    } catch (PDOException $e) {
        $error = "Error fetching pools for division {$division}: " . $e->getMessage();
    }
}
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
        max-width: 1400px;
        margin: 0 auto;
    }
    .pool-table-container {
        overflow-x: auto; /* Allow scrolling on very small screens as a fallback */
        background-color: #1a1a1a;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        margin-bottom: 30px;
        padding: 5px;
        transition: all 0.3s ease;
    }

    @media (min-width: 576px) {
        /* On screens wider than 576px, we can fit everything without scrolling */
        .pool-table-container {
            overflow-x: hidden;
        }
    }

    .pool-table {
        width: 100%;
        margin-bottom: 0;
        border-radius: 8px;
        overflow: hidden;
        border-collapse: separate;
        border-spacing: 0;
        min-width: 450px; /* Ensures we don't go too narrow */
    }
    .pool-table thead th {
        background-color: #2a2a2a;
        color: #fff;
        border-bottom: 2px solid #FF6B00;
        padding: 8px 2px;
        text-align: center;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .pool-table tbody tr {
        transition: background-color 0.2s ease;
    }
    .pool-table tbody tr:nth-child(odd) {
        background-color: rgba(255, 255, 255, 0.05);
    }
    .pool-table tbody tr:nth-child(even) {
        background-color: rgba(255, 255, 255, 0.02);
    }
    .pool-table tbody tr:hover {
        background-color: rgba(255, 107, 0, 0.1);
    }
    .pool-table td {
        padding: 8px 2px;
        text-align: center;
        border-top: 1px solid rgba(255, 255, 255, 0.05);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .pool-table th.team-name, .pool-table td.team-name {
        width: 140px; /* Fixed width for team name column */
        min-width: 140px; /* Ensure it doesn't get smaller */
        text-align: left;
        position: relative;
        padding-left: 15px;
    }
    .pool-table th.stat, .pool-table td.stat {
        width: auto; /* Let the browser determine optimal width */
        min-width: 35px; /* Ensure a minimum width */
    }
    /* Position indicators for top 3 teams */
    .position-1 .team-name::before,
    .position-2 .team-name::before,
    .position-3 .team-name::before {
        content: "";
        position: absolute;
        left: 5px;
        top: 50%;
        transform: translateY(-50%);
        width: 6px;
        height: 6px;
        border-radius: 50%;
    }
    .position-1 .team-name::before {
        background-color: #FFD700; /* Gold */
    }
    .position-2 .team-name::before {
        background-color: #C0C0C0; /* Silver */
    }
    .position-3 .team-name::before {
        background-color: #CD7F32; /* Bronze */
    }
    .pool-heading {
        background-color: #2d2d2d;
        color: #fff;
        font-size: 1.2em;
        padding: 12px 15px;
        margin-bottom: 0;
        text-align: center;
        display: block;
        border-top-left-radius: 10px;
        border-top-right-radius: 10px;
        border-left: 3px solid #FF6B00;
        border-right: 3px solid #FF6B00;
        border-top: 3px solid #FF6B00;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
    }
    .division-heading {
        background: linear-gradient(135deg, #333333 0%, #222222 100%);
        color: #fff;
        font-size: 1.5em;
        padding: 15px;
        margin-top: 30px;
        margin-bottom: 20px;
        text-align: center;
        display: block;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border-left: 5px solid #FF6B00;
        position: relative;
        overflow: hidden;
    }
    .division-heading::after {
        content: "";
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        width: 5px;
        background-color: #FF6B00;
    }
    /* Adjusting the team name column width and table font sizes on smaller screens */
    @media (max-width: 1200px) {
        .pool-table th, .pool-table td {
            font-size: 0.9em;
        }
        .pool-table .team-name {
            width: 200px;
        }
    }
    @media (max-width: 992px) {
        .pool-table .team-name {
            width: 180px;
        }
    }
    @media (max-width: 768px) {
        .pool-table th, .pool-table td {
            font-size: 0.8em;
            padding: 8px 5px;
        }
        .division-heading {
            font-size: 1.3em;
            padding: 12px;
        }
    }
    @media (max-width: 576px) {
        .pool-table th, .pool-table td {
            font-size: 0.75em;
            padding: 6px 4px;
        }
        .pool-table .team-name {
            width: 160px;
        }
        .pool-heading {
            font-size: 1em;
            padding: 10px;
        }
        .division-heading {
            font-size: 1.1em;
            padding: 10px;
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

<div class="container-fluid">
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (empty($divisionPoolsData)): ?>
        <div class="alert alert-info">No standings data available. Please assign teams to pools first.</div>
    <?php else: ?>
        <?php foreach ($divisionPoolsData as $division => $poolsData): ?>
            <div class="division-heading"><?php echo htmlspecialchars($division); ?> Division</div>
            
            <?php 
            $poolCount = count($poolsData);
            $colSize = ($poolCount > 0) ? 12 / min($poolCount, 2) : 12; // Maximum of 2 pools per row
            
            for ($i = 0; $i < $poolCount; $i += 2): // Process in pairs for two columns per row
            ?>
                <div class="row">
                    <?php for ($j = $i; $j < min($i + 2, $poolCount); $j++): // Process this pair of pools ?>
                        <div class="col-lg-<?php echo $colSize; ?> col-md-12">
                            <h3 class="pool-heading">Pool <?php echo htmlspecialchars($poolsData[$j]['pool_name']); ?></h3>
                            <div class="pool-table-container">
                                <?php 
                                // Get standings data for this pool
                                $poolStandings = $poolsData[$j]['standings'];
                                
                                if (empty($poolStandings)): ?>
                                    <div class="alert alert-info">No teams assigned to this pool yet.</div>
                                <?php else: ?>
                                    <table class="table table-dark pool-table">
                                        <thead>
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
                                        <tbody>
                                            <?php foreach ($poolStandings as $index => $row): 
                                                // Add position class for top 3 teams
                                                $positionClass = ($index < 3) ? 'position-' . ($index + 1) : '';
                                            ?>
                                                <tr class="<?php echo $positionClass; ?>">
                                                    <td class="team-name"><?php echo htmlspecialchars($row['team_name']); ?></td>
                                                    <td class="stat"><?php echo $row['games_played']; ?></td>
                                                    <td class="stat"><?php echo $row['wins']; ?></td>
                                                    <td class="stat"><?php echo $row['losses']; ?></td>
                                                    <td class="stat"><?php echo $row['points_for']; ?></td>
                                                    <td class="stat"><?php echo $row['points_against']; ?></td>
                                                    <td class="stat"><?php echo $row['plus_minus']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            <?php endfor; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

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

<?php
// assign_pools.php
// Allows administrators to assign teams to pools

session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: admin.php');
    exit;
}

// Include the database connection
require_once '/home/lostan6/springshootout.ca/includes/config.php';
require __DIR__ . '/../scripts/php/db_connect.php';

$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Process each team-pool assignment
        foreach ($_POST['pool'] as $teamId => $poolId) {
            if (empty($poolId)) {
                // If no pool is selected, check if team has a pool assigned and remove it
                $deleteStmt = $pdo->prepare("DELETE FROM team_pools WHERE team_id = :team_id");
                $deleteStmt->execute([':team_id' => $teamId]);
            } else {
                // Check if the team already has a pool assignment
                $checkStmt = $pdo->prepare("SELECT pool_id FROM team_pools WHERE team_id = :team_id");
                $checkStmt->execute([':team_id' => $teamId]);
                $existingPool = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existingPool) {
                    // Update existing assignment
                    $updateStmt = $pdo->prepare("UPDATE team_pools SET pool_id = :pool_id WHERE team_id = :team_id");
                    $updateStmt->execute([
                        ':pool_id' => $poolId,
                        ':team_id' => $teamId
                    ]);
                } else {
                    // Create new assignment
                    $insertStmt = $pdo->prepare("INSERT INTO team_pools (team_id, pool_id) VALUES (:team_id, :pool_id)");
                    $insertStmt->execute([
                        ':team_id' => $teamId,
                        ':pool_id' => $poolId
                    ]);
                }
            }
        }
        
        $pdo->commit();
        $message = '<div class="alert alert-success">Pool assignments have been updated successfully.</div>';
    } catch (PDOException $e) {
        $pdo->rollBack();
        $message = '<div class="alert alert-danger">Error updating pool assignments: ' . $e->getMessage() . '</div>';
    }
}

// Fetch all pools
try {
    $poolsStmt = $pdo->query("SELECT pool_id, pool_name FROM pools ORDER BY pool_name");
    $pools = $poolsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = '<div class="alert alert-danger">Error fetching pools: ' . $e->getMessage() . '</div>';
    $pools = [];
}

// Fetch teams grouped by division with current pool assignment
try {
    $teamsStmt = $pdo->query("
        SELECT 
            r.team_id, 
            t.team_name, 
            r.division,
            tp.pool_id as current_pool_id
        FROM 
            registrations r
        JOIN 
            teams t ON r.team_id = t.team_id
        LEFT JOIN 
            team_pools tp ON r.team_id = tp.team_id
        WHERE 
            r.year = 2025 AND r.status = 1
        ORDER BY 
            r.division, t.team_name
    ");
    $allTeams = $teamsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group teams by division
    $teamsByDivision = [];
    foreach ($allTeams as $team) {
        $division = $team['division'];
        if (!isset($teamsByDivision[$division])) {
            $teamsByDivision[$division] = [];
        }
        $teamsByDivision[$division][] = $team;
    }
} catch (PDOException $e) {
    $message = '<div class="alert alert-danger">Error fetching teams: ' . $e->getMessage() . '</div>';
    $teamsByDivision = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Teams to Pools</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-aFq/bzH65dt+w6FI2ooMVUpc+21e0SRygnTpmBvdBgSdnuTN7QbdgL+OapgHtvPp" crossorigin="anonymous">
    <!-- Custom Stylesheet -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background-color: black;
            color: white;
        }
        .team-row:nth-child(odd) {
            background-color: rgba(255, 255, 255, 0.05);
        }
        .team-row:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        .division-header {
            background-color: #343a40;
            color: white;
            padding: 10px;
            margin-top: 20px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        .form-control, .form-select {
            background-color: #212529;
            color: white;
            border-color: #495057;
        }
        .form-control:focus, .form-select:focus {
            background-color: #2c3034;
            color: white;
        }
        .form-select option {
            background-color: #212529;
            color: white;
        }
        .table-dark {
            --bs-table-bg: transparent;
        }
    </style>
</head>
<body>

<div class="container">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark justify-content-center">
        <div id="nav-placeholder"></div>
    </nav>
</div>

<!-- Content of the page -->
<div class="poster">
    <img src="../assets/images/name.png" alt="Spring Shootout">
</div>
<div class="container mt-4">
    <h1 class="text-center mb-4">Assign Teams to Pools</h1>
    
    <?php echo $message; ?>
    
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <?php foreach ($teamsByDivision as $division => $teams): ?>
                            <h3 class="division-header"><?php echo htmlspecialchars($division); ?> Division</h3>
                            <table class="table table-dark">
                                <thead>
                                    <tr>
                                        <th>Team Name</th>
                                        <th>Pool Assignment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($teams as $team): ?>
                                        <tr class="team-row">
                                            <td><?php echo htmlspecialchars($team['team_name']); ?></td>
                                            <td>
                                                <select name="pool[<?php echo $team['team_id']; ?>]" class="form-select">
                                                    <option value="">-- No Pool --</option>
                                                    <?php foreach ($pools as $pool): ?>
                                                        <option value="<?php echo $pool['pool_id']; ?>" <?php echo ($team['current_pool_id'] == $pool['pool_id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($pool['pool_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endforeach; ?>
                        
                        <div class="d-grid gap-2 col-6 mx-auto mt-4">
                            <button type="submit" class="btn btn-primary">Save Pool Assignments</button>
                            <a href="menu.php" class="btn btn-secondary">Back to Menu</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
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
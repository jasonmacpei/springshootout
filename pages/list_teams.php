<?php
session_start(); // Start the session

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: admin.php');
    exit;
}

// list_teams.php
// Display registered teams grouped dynamically by division

// Enable error reporting for development
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include the configuration file (for error logging, etc.)
require_once '/home/lostan6/springshootout.ca/includes/config.php';
// Include the database connection file
require __DIR__ . '/../scripts/php/db_connect.php';

// Define the SQL query to fetch teams and their contact information for the current tournament
$query = "
WITH ranked_contacts AS (
    SELECT
        r.registration_id,
        t.team_name,
        c.contact_name,
        c.email_address,
        c.phone_number,
        r.division,
        r.class,
        r.paid,
        -- Assign a row number within each team partition,
        -- ordered by tc.role (you can change this if needed)
        ROW_NUMBER() OVER (
            PARTITION BY r.team_id
            ORDER BY tc.role_id
        ) AS rn
    FROM registrations r
    JOIN teams t ON r.team_id = t.team_id
    JOIN team_contacts tc ON tc.team_id = r.team_id
    JOIN contacts c ON tc.contact_id = c.contact_id
    WHERE r.status = 1
)
SELECT *
FROM ranked_contacts
WHERE rn = 1;
";

try {
    // Execute the query
    $stmt = $pdo->query($query);
    // Fetch all the results as an associative array
    $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If there is an error, output it and stop execution
    echo "Error: " . htmlspecialchars($e->getMessage());
    die();
}

// Group teams by their division dynamically
$teams_by_division = array();
foreach ($teams as $team) {
    // Normalize division key to lowercase for consistency
    $division = strtolower($team['division']);
    if (!isset($teams_by_division[$division])) {
        $teams_by_division[$division] = array();
    }
    $teams_by_division[$division][] = $team;
}

// Define the order of divisions
$division_order = array('u11', 'u12', 'u13');

// Sort teams within each division if needed (optional)
foreach ($teams_by_division as &$division_teams) {
    usort($division_teams, function($a, $b) {
        return strcmp($a['team_name'], $b['team_name']);
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spring Shootout - Registered Teams</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-aFq/bzH65dt+w6FI2ooMVUpc+21e0SRygnTpmBvdBgSdnuTN7QbdgL+OapgHtvPp" crossorigin="anonymous">
    <!-- Custom Stylesheet -->
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <div class="container">
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark justify-content-center">
            <div id="nav-placeholder"></div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="container my-4">
        <?php if (empty($teams_by_division)): ?>
            <p class="text-danger">No teams registered at this time.</p>
        <?php else: ?>
            <?php foreach ($division_order as $division): ?>
                <?php if (isset($teams_by_division[$division]) && !empty($teams_by_division[$division])): ?>
                <!-- Use the division value dynamically in the heading -->
                <h1 class="mt-4"><?php echo strtoupper(htmlspecialchars($division)); ?> Registered Teams</h1>
                <table class="table table-dark table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Team Name</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Division</th>
                            <th>Paid</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rowNumber = 1; ?>
                        <?php foreach ($teams_by_division[$division] as $team): ?>
                            <tr>
                                <td><?php echo $rowNumber++; ?></td>
                                <td><?php echo htmlspecialchars($team['team_name']); ?></td>
                                <td><?php echo htmlspecialchars($team['contact_name']); ?></td>
                                <td><?php echo htmlspecialchars($team['email_address']); ?></td>
                                <td><?php echo htmlspecialchars($team['phone_number']); ?></td>
                                <td><?php echo htmlspecialchars($team['division']); ?></td>
                                <td><?php echo (strtoupper($team['paid']) === '1' ? 'Yes' : ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- jQuery and Bootstrap JS -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
            crossorigin="anonymous"></script>
    <!-- Load the navbar dynamically -->
    <script>
        $(document).ready(function() {
            $("#nav-placeholder").load("/includes/navbar.html");
        });
    </script>
</body>
</html>
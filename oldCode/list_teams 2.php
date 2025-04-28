<?php
// list_teams.php
// Display registered teams grouped dynamically by division

// Include configuration file first (which now handles error reporting)
if (file_exists('/home/lostan6/springshootout.ca/includes/config.php')) {
    require_once '/home/lostan6/springshootout.ca/includes/config.php';
} else {
    require_once __DIR__ . '/../includes/config.php';
}

// Include the database connection file
require_once __DIR__ . '/../scripts/php/db_connect.php';

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
        -- ordered by tc.role_id for proper ordering of contacts
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
    // Log the error and display a user-friendly message
    error_log("Error fetching teams: " . $e->getMessage());
    echo '<div class="alert alert-danger">Unable to fetch team data. Please try again later.</div>';
    $teams = [];
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

// Get division order from config or use a default order
$division_order = defined('DIVISION_ORDER') ? json_decode(DIVISION_ORDER, true) : ['u10', 'u11', 'u12', 'u13', 'u14', 'u15', 'u16'];

// Sort teams within each division by team name
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
        <h1 class="mb-4">Registered Teams</h1>

        <?php if (empty($teams_by_division)): ?>
            <div class="alert alert-info">No teams registered at this time.</div>
        <?php else: ?>
            <?php 
            // First check for divisions that exist in data but aren't in order list
            $extra_divisions = array_diff(array_keys($teams_by_division), $division_order);
            // Display all ordered divisions first
            foreach ($division_order as $division): 
                if (isset($teams_by_division[$division]) && !empty($teams_by_division[$division])): 
            ?>
                <!-- Use the division value dynamically in the heading -->
                <h2 class="mt-4"><?php echo strtoupper(htmlspecialchars($division)); ?> Division</h2>
                <table class="table table-dark table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Team Name</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Class</th>
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
                                <td><?php echo htmlspecialchars($team['class']); ?></td>
                                <td><?php echo (isset($team['paid']) && $team['paid'] == '1' ? 'Yes' : 'No'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            <?php endforeach; ?>

            <!-- Now display any extra divisions not in the ordered list -->
            <?php foreach ($extra_divisions as $division): ?>
                <h2 class="mt-4"><?php echo strtoupper(htmlspecialchars($division)); ?> Division</h2>
                <table class="table table-dark table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Team Name</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Class</th>
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
                                <td><?php echo htmlspecialchars($team['class']); ?></td>
                                <td><?php echo (isset($team['paid']) && $team['paid'] == '1' ? 'Yes' : 'No'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
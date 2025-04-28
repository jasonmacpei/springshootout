<?php
// list_contacts.php
//
// Displays two sections:
// 1) A list of all contacts (name, phone, email).
// 2) A list of all teams with a numbered list of contacts for each team.
//
// Follows the same styling (navbar, black background, white text) as your other pages.

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: admin.php');
    exit;
}

// Include configuration and DB connection
require_once '/home/lostan6/springshootout.ca/includes/config.php';
require_once __DIR__ . '/../scripts/php/db_connect.php';

// Part 1: Fetch all contacts
$contacts = [];
try {
    $contactsSql = "SELECT contact_id, contact_name, email_address, phone_number
                    FROM contacts
                    ORDER BY contact_name";
    $stmtContacts = $pdo->query($contactsSql);
    $contacts = $stmtContacts->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log or handle error
    $errorMessage = "Error fetching contacts: " . $e->getMessage();
    // In production, you might show a user-friendly message or log the error
}

// Part 2: Fetch all teams
$teams = [];
try {
    $teamsSql = "SELECT team_id, team_name
                 FROM teams
                 ORDER BY team_name";
    $stmtTeams = $pdo->query($teamsSql);
    $teams = $stmtTeams->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log or handle error
    $errorMessage = "Error fetching teams: " . $e->getMessage();
}

// We'll fetch contacts for each team in a loop below.

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>List Contacts</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" 
          rel="stylesheet" integrity="sha384-..." crossorigin="anonymous">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        body {
            background-color: black;
            color: white;
        }
        .container {
            margin-top: 20px;
        }
        .card {
            background-color: #222;
            border: 1px solid #444;
        }
        .card-header {
            background-color: #333;
        }
    </style>
</head>
<body>
    <!-- Navbar container and placeholder -->
    <div class="container">
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark justify-content-center">
            <div id="nav-placeholder">
                <?php include('../includes/navbar.html'); ?>
            </div>
        </nav>
    </div>

    <div class="container">
        <!-- Part 1: All Contacts -->
        <div class="card mb-4">
            <div class="card-header">
                <h2>All Contacts</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($contacts)): ?>
                    <table class="table table-dark table-striped">
                        <thead>
                            <tr>
                                <th>Contact Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contacts as $contact): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($contact['contact_name']); ?></td>
                                    <td><?php echo htmlspecialchars($contact['phone_number']); ?></td>
                                    <td><?php echo htmlspecialchars($contact['email_address']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No contacts found.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Part 2: Teams & Their Contacts -->
        <div class="card mb-4">
            <div class="card-header">
                <h2>Teams &amp; Their Contacts</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($teams)): ?>
                    <table class="table table-dark table-striped">
                        <thead>
                            <tr>
                                <th>Team Name</th>
                                <th>Contacts (Numbered List)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($teams as $team):
                                // For each team, fetch associated contacts
                                $teamId = $team['team_id'];
                                $teamContacts = [];
                                try {
                                    $teamContactsSql = "
                                        SELECT c.contact_id, c.contact_name
                                        FROM team_contacts tc
                                        JOIN contacts c ON c.contact_id = tc.contact_id
                                        WHERE tc.team_id = :team_id
                                        ORDER BY c.contact_name
                                    ";
                                    $stmtTC = $pdo->prepare($teamContactsSql);
                                    $stmtTC->execute([':team_id' => $teamId]);
                                    $teamContacts = $stmtTC->fetchAll(PDO::FETCH_ASSOC);
                                } catch (PDOException $e) {
                                    // Log or handle error
                                    $errorMessage = "Error fetching team contacts: " . $e->getMessage();
                                }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($team['team_name']); ?></td>
                                    <td>
                                        <?php if (!empty($teamContacts)): ?>
                                            <ol>
                                                <?php foreach ($teamContacts as $tc): ?>
                                                    <li><?php echo htmlspecialchars($tc['contact_name']); ?></li>
                                                <?php endforeach; ?>
                                            </ol>
                                        <?php else: ?>
                                            <em>No contacts assigned to this team.</em>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No teams found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" 
            integrity="sha384-..." crossorigin="anonymous"></script>
</body>
</html>
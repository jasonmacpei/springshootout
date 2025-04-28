<?php
// This is a test
// add_contact_admin.php
//
// This page allows an admin to add a new contact and optionally associate the contact with a team.
// Detailed descriptions are provided for each form field.
// It uses Bootstrap 5, a custom CSS file, and includes the common navbar.
// Error handling is performed via try/catch blocks, with error messages stored in $message.

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

$message = '';

// Fetch available roles from the contact_roles table
$roles = [];
try {
    $rolesSql = "SELECT role_id, role_name FROM contact_roles ORDER BY role_name";
    $stmtRoles = $pdo->query($rolesSql);
    $roles = $stmtRoles->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error fetching roles: " . $e->getMessage();
}

// Fetch available teams from the teams table
$teams = [];
try {
    $teamsSql = "SELECT team_id, team_name FROM teams ORDER BY team_name";
    $stmtTeams = $pdo->query($teamsSql);
    $teams = $stmtTeams->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error fetching teams: " . $e->getMessage();
}

// Process the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data with basic sanitization
    $contactName  = trim($_POST['contact_name'] ?? '');
    $emailAddress = trim($_POST['email_address'] ?? '');
    $phoneNumber  = trim($_POST['phone_number'] ?? '');
    $roleId       = trim($_POST['role_id'] ?? '');
    $teamId       = trim($_POST['team_id'] ?? '');  // Optional team association

    // Validate required fields
    if (empty($contactName)) {
        $message = "Contact Name is required.";
    } else {
        try {
            // Insert new contact into the contacts table
            $sqlInsert = "INSERT INTO contacts (contact_name, email_address, phone_number, role_id)
                          VALUES (:contact_name, :email_address, :phone_number, :role_id)";
            $stmtInsert = $pdo->prepare($sqlInsert);
            $stmtInsert->execute([
                ':contact_name'  => $contactName,
                ':email_address' => $emailAddress,
                ':phone_number'  => $phoneNumber,
                ':role_id'       => $roleId
            ]);

            // Get the new contact's ID
            $newContactId = $pdo->lastInsertId();

            // If a team is selected, insert the relationship into the team_contacts table.
            // We supply a default value (empty string) for the "role" column since it's required.
            if (!empty($teamId)) {
                $sqlTeamContact = "INSERT INTO team_contacts (team_id, contact_id, role_id, created_at, updated_at)
                                   VALUES (:team_id, :contact_id, :role_id, NOW(), NOW())";
                $stmtTC = $pdo->prepare($sqlTeamContact);
                $stmtTC->execute([
                    ':team_id'    => $teamId,
                    ':contact_id' => $newContactId,
                    ':role_id'    => $roleId  // Using the same roleId from the form
                ]);
            }

            $message = "New contact added successfully.";
        } catch (PDOException $e) {
            $message = "Error inserting contact: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Contact (Admin)</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Ensure overall page styling: black background, white text */
        body {
            background-color: black !important;
            color: white !important;
        }
        .container {
            margin-top: 20px;
        }
        /* Card styling matching other pages */
        .card {
            background-color: #222 !important;
            border: 1px solid #444;
        }
        .card-header {
            background-color: #333 !important;
        }
        /* Force form labels and texts to white */
        .form-label,
        .form-text {
            color: white !important;
        }
        /* Force form-control text color to white and background dark */
        .form-control {
            background-color: #333 !important;
            color: white !important;
            border: 1px solid #555;
        }
        /* Button styling */
        .btn-primary {
            background-color: #0069d9;
            border-color: #0062cc;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="container">
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark justify-content-center">
            <div id="nav-placeholder">
                <?php include('../includes/navbar.html'); ?>
            </div>
        </nav>
    </div>

    <!-- Main Content Container -->
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Add New Contact (Admin)</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-info"><?php echo $message; ?></div>
                <?php endif; ?>

                <form action="add_contact_admin.php" method="POST">
                    <!-- Contact Name -->
                    <div class="mb-3">
                        <label for="contact_name" class="form-label">Contact Name</label>
                        <input type="text" name="contact_name" id="contact_name" class="form-control" required>
                        <small class="form-text text-muted">
                            Enter the full name of the contact (e.g., John Doe).
                        </small>
                    </div>

                    <!-- Email Address -->
                    <div class="mb-3">
                        <label for="email_address" class="form-label">Email Address</label>
                        <input type="email" name="email_address" id="email_address" class="form-control">
                        <small class="form-text text-muted">
                            Enter the contact's email address (e.g., john@example.com).
                        </small>
                    </div>

                    <!-- Phone Number -->
                    <div class="mb-3">
                        <label for="phone_number" class="form-label">Phone Number</label>
                        <input type="text" name="phone_number" id="phone_number" class="form-control">
                        <small class="form-text text-muted">
                            Enter the contact's phone number (e.g., 555-123-4567).
                        </small>
                    </div>

                    <!-- Role -->
                    <div class="mb-3">
                        <label for="role_id" class="form-label">Role</label>
                        <select name="role_id" id="role_id" class="form-control">
                            <option value="">-- Optional: Select a role for the contact --</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo htmlspecialchars($role['role_id']); ?>">
                                    <?php echo htmlspecialchars($role['role_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">
                            Choose the contact's role (e.g., Head Coach, Manager) as defined in the system.
                        </small>
                    </div>

                    <!-- Team -->
                    <div class="mb-3">
                        <label for="team_id" class="form-label">Team (Optional)</label>
                        <select name="team_id" id="team_id" class="form-control">
                            <option value="">-- No Team Selected --</option>
                            <?php foreach ($teams as $team): ?>
                                <option value="<?php echo htmlspecialchars($team['team_id']); ?>">
                                    <?php echo htmlspecialchars($team['team_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">
                            Optionally, assign this contact to a team. If not selected, the contact is added without team association.
                        </small>
                    </div>

                    <button type="submit" class="btn btn-primary">Add Contact</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
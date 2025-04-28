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

// Fetch teams for the dropdown
try {
    $teamsStmt = $pdo->query("SELECT team_id, team_name FROM teams ORDER BY team_name");
    $teams = $teamsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching teams: " . $e->getMessage());
}

// Fetch roles for the dropdown
try {
    $rolesStmt = $pdo->query("SELECT role_id, role_name FROM contact_roles ORDER BY role_name");
    $roles = $rolesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching roles: " . $e->getMessage());
}

// Process the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data with basic sanitization
    $contactName  = trim($_POST['contact_name'] ?? '');
    $emailAddress = trim($_POST['email_address'] ?? '');
    $phoneNumber  = trim($_POST['phone_number'] ?? '');
    $roleId       = trim($_POST['role'] ?? '');
    $teamId       = trim($_POST['team'] ?? '');  // Optional team association

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
                $sqlTeamContact = "INSERT INTO team_contacts (team_id, contact_id, role)
                                   VALUES (:team_id, :contact_id, :role)";
                $stmtTC = $pdo->prepare($sqlTeamContact);
                $stmtTC->execute([
                    ':team_id'    => $teamId,
                    ':contact_id' => $newContactId,
                    ':role'       => $roleId
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
    <title>Add New Contact (Admin)</title>
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
        .form-control:focus {
            background-color: #444;
            color: white;
            border-color: #666;
        }
        .alert {
            background-color: #2d1c1c;
            border-color: #ff6b6b;
            color: #ff6b6b;
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

                <div id="errorMessage" class="alert alert-danger" style="display: none;"></div>
                <form id="addContactForm">
                    <!-- Contact Name -->
                    <div class="mb-3">
                        <label for="contact_name" class="form-label">Contact Name</label>
                        <input type="text" class="form-control" id="contact_name" name="contact_name" required>
                        <small class="form-text text-muted">
                            Enter the full name of the contact (e.g., John Doe).
                        </small>
                    </div>

                    <!-- Email Address -->
                    <div class="mb-3">
                        <label for="email_address" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email_address" name="email_address">
                        <small class="form-text text-muted">
                            Enter the contact's email address (e.g., john@example.com).
                        </small>
                    </div>

                    <!-- Phone Number -->
                    <div class="mb-3">
                        <label for="phone_number" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone_number" name="phone_number">
                        <small class="form-text text-muted">
                            Enter the contact's phone number (e.g., 555-123-4567).
                        </small>
                    </div>

                    <!-- Team (Optional) -->
                    <div class="mb-3">
                        <label for="team" class="form-label">Team (Optional)</label>
                        <select class="form-control" id="team" name="team">
                            <option value="">-- No Team Selected --</option>
                            <?php foreach ($teams as $team): ?>
                                <option value="<?php echo htmlspecialchars($team['team_id']); ?>">
                                    <?php echo htmlspecialchars($team['team_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">
                            Optionally, assign this contact to a team.
                        </small>
                    </div>

                    <!-- Role (Optional) -->
                    <div class="mb-3">
                        <label for="role" class="form-label">Role (Optional)</label>
                        <select class="form-control" id="role" name="role">
                            <option value="">-- No Role Selected --</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo htmlspecialchars($role['role_id']); ?>">
                                    <?php echo htmlspecialchars($role['role_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">
                            Choose the contact's role (e.g., Head Coach, Manager).
                        </small>
                    </div>

                    <button type="submit" class="btn btn-primary">Add Contact</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#addContactForm').submit(function(e) {
                e.preventDefault();
                
                // Clear any previous error messages
                $('#errorMessage').hide();
                
                $.ajax({
                    url: '/scripts/php/add_contact_process.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        // Show success message and reset form
                        $('#errorMessage')
                            .removeClass('alert-danger')
                            .addClass('alert-success')
                            .html('Contact added successfully!')
                            .show();
                        $('#addContactForm')[0].reset();
                        
                        // Hide success message after 5 seconds
                        setTimeout(function() {
                            $('#errorMessage').fadeOut();
                        }, 5000);
                    },
                    error: function(xhr, status, error) {
                        // Show error message
                        $('#errorMessage')
                            .removeClass('alert-success')
                            .addClass('alert-danger')
                            .html(xhr.responseText)
                            .show();
                    }
                });
            });
        });
    </script>
</body>
</html>
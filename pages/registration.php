<?php
// pages/registration.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the database connection to fetch any dynamic data
require_once '../scripts/php/db_connect.php';
// Optionally include config.php if needed (but db_connect.php should have done that)
// require_once '/home/lostan6/springshootout.ca/includes/config.php';

// Load PHPMailer via Composer autoload
require_once '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Fetch contact roles to populate the dropdown
$contactRoles = [];
try {
    $roleSql = "SELECT role_id, role_name FROM contact_roles ORDER BY role_name";
    $stmtRole = $pdo->query($roleSql);
    $contactRoles = $stmtRole->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching contact roles: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Registration</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background-color: black !important; color: white !important; }
        .container { margin-top: 20px; }
        .card { background-color: #222; border: 1px solid #444; }
        .card-header { background-color: #333; }
        .form-label, .form-text { color: white !important; }
        .form-control { background-color: #333 !important; color: white !important; border: 1px solid #555; }
        .form-control::placeholder { color: #bbb; }
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
    <!-- Registration Form -->
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Registration</h2>
            </div>
            <div class="card-body">
                <form action="../scripts/php/register.php" method="POST">
                    <div class="mb-3">
                        <label for="team_name" class="form-label">Team Name:</label>
                        <input type="text" name="team_name" id="team_name" class="form-control" required placeholder="Enter team name">
                        <small class="form-text text-muted">Enter the name of your team.</small>
                    </div>
                    <div class="mb-3">
                        <label for="contact_name" class="form-label">Contact Name:</label>
                        <input type="text" name="contact_name" id="contact_name" class="form-control" required placeholder="Full name">
                        <small class="form-text text-muted">Enter the primary contact's full name.</small>
                    </div>
                    <div class="mb-3">
                        <label for="contact_role" class="form-label">Contact Role:</label>
                        <select name="contact_role" id="contact_role" class="form-control" required>
                            <option value="">-- Select Role --</option>
                            <?php foreach ($contactRoles as $role): ?>
                                <option value="<?php echo htmlspecialchars($role['role_id']); ?>">
                                    <?php echo htmlspecialchars($role['role_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Select the contact's role (e.g., Head Coach, Manager).</small>
                    </div>
                    <div class="mb-3">
                        <label for="province" class="form-label">Province:</label>
                        <input type="text" name="province" id="province" class="form-control" required placeholder="e.g., Ontario">
                        <small class="form-text text-muted">Enter the province where your team is located.</small>
                    </div>
                    <div class="mb-3">
                        <label for="division" class="form-label">Division:</label>
                        <select name="division" id="division" class="form-control" required>
                            <option value="">-- Select Division --</option>
                            <option value="u11">u11</option>
                            <option value="u12">u12</option>
                            <option value="u13">u13</option>
                        </select>
                        <small class="form-text text-muted">Select the division for your team.</small>
                    </div>
                    <div class="mb-3">
                        <label for="class" class="form-label">Class:</label>
                        <select name="class" id="class" class="form-control" required>
                            <option value="">-- Select Class --</option>
                            <option value="Division 1">Division 1</option>
                            <option value="Division 2">Division 2</option>
                            <option value="Division 3">Division 3</option>
                            <option value="Division 4">Division 4</option>
                            <option value="Unknown">Unknown</option>
                        </select>
                        <small class="form-text text-muted">Select your team's class.</small>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email:</label>
                        <input type="email" name="email" id="email" class="form-control" required placeholder="contact@example.com">
                        <small class="form-text text-muted">Enter the contact's email address.</small>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone:</label>
                        <input type="text" name="phone" id="phone" class="form-control" required placeholder="555-123-4567">
                        <small class="form-text text-muted">Enter the contact's phone number.</small>
                    </div>
                    <div class="mb-3">
                        <label for="note" class="form-label">Note:</label>
                        <textarea name="note" id="note" class="form-control" rows="3" placeholder="Optional notes"></textarea>
                        <small class="form-text text-muted">Any additional notes regarding your registration.</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Register</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
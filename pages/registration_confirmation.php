<?php
// registration_confirmation.php
// This page confirms that a registration was successful and offers an option
// to add additional contacts. The team_id is passed via GET (e.g., registration_confirmation.php?team_id=21).
// It uses the same styling as other pages: black background, white text, Bootstrap 5, and a common navbar.

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
// Optionally, check if the user is logged in if this page is for admins only
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: admin.php');
    exit;
}

// Get the team_id from GET parameter
$teamId = isset($_GET['team_id']) ? intval($_GET['team_id']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Registration Confirmation</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background-color: black !important;
            color: white !important;
        }
        .container {
            margin-top: 20px;
        }
        .card {
            background-color: #222 !important;
            border: 1px solid #444;
        }
        .card-header {
            background-color: #333 !important;
        }
        .btn-primary {
            background-color: #0069d9;
            border-color: #0062cc;
        }
    </style>
</head>
<body>
    <!-- Navbar inclusion for consistent styling -->
    <div class="container">
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark justify-content-center">
            <div id="nav-placeholder">
                <?php include('../includes/navbar.html'); ?>
            </div>
        </nav>
    </div>

    <!-- Main Confirmation Content -->
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Registration Confirmation</h2>
            </div>
            <div class="card-body">
                <p>Your registration was successful!</p>
                <?php if ($teamId > 0): ?>
                    <p>Would you like to add additional contacts to your team?</p>
                    <a href="contacts.php?team_id=<?php echo $teamId; ?>" class="btn btn-primary">Add Additional Contacts</a>
                <?php else: ?>
                    <p>You can now manage your contacts from the admin panel.</p>
                    <a href="contacts.php" class="btn btn-primary">Manage Contacts</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
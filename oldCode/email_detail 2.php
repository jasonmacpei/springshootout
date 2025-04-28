<?php
// email_detail.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// For pages, use this path:
require_once '../scripts/php/db_connect.php';
require_once '/home/lostan6/springshootout.ca/includes/config.php';

// Validate and get the email ID from GET parameters
if (!isset($_GET['email_id']) || !is_numeric($_GET['email_id'])) {
    die("Invalid email ID.");
}

$email_id = (int)$_GET['email_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM sent_emails WHERE email_id = :email_id");
    $stmt->execute([':email_id' => $email_id]);
    $email = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$email) {
        die("Email not found.");
    }
    $dt = new DateTime($email['sent_at']);
    $date = $dt->format('Y-m-d');
    $time = $dt->format('H:i:s');
} catch (PDOException $e) {
    die("Error fetching email details: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Detail - ID <?php echo htmlspecialchars($email['email_id']); ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-..." crossorigin="anonymous">
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
        /* Card styling */
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
    <!-- Navbar with common styling -->
    <div class="container">
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark justify-content-center">
            <div id="nav-placeholder">
                <?php include('../includes/navbar.html'); ?>
            </div>
        </nav>
    </div>

    <!-- Main Content Container -->
    <div class="container">
        <div class="card mt-4">
            <div class="card-header">
                <h2>Email Detail - ID <?php echo htmlspecialchars($email['email_id']); ?></h2>
            </div>
            <div class="card-body">
                <table class="table table-dark table-bordered">
                    <tr>
                        <th>Email ID</th>
                        <td><?php echo htmlspecialchars($email['email_id']); ?></td>
                    </tr>
                    <tr>
                        <th>Sent Date</th>
                        <td><?php echo $date; ?></td>
                    </tr>
                    <tr>
                        <th>Sent Time</th>
                        <td><?php echo $time; ?></td>
                    </tr>
                    <tr>
                        <th>To</th>
                        <td><?php echo htmlspecialchars($email['recipient']); ?></td>
                    </tr>
                    <tr>
                        <th>Subject</th>
                        <td><?php echo htmlspecialchars($email['subject']); ?></td>
                    </tr>
                    <tr>
                        <th>Message</th>
                        <td>
                            <pre style="white-space: pre-wrap;"><?php echo htmlspecialchars($email['body']); ?></pre>
                        </td>
                    </tr>
                </table>
                <a href="send_email.php" class="btn btn-primary">Back to Send Email</a>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-..." crossorigin="anonymous"></script>
</body>
</html>
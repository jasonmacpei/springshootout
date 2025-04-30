<?php
session_start(); // Start the session

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: admin.php');
    exit;
}

// edit_welcome_email.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../scripts/php/db_connect.php';
require_once '/home/lostan6/springshootout.ca/includes/config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subject = trim($_POST['subject']);
    $body    = trim($_POST['body']);
    
    if (empty($subject) || empty($body)) {
        $message = "Please provide both a subject and a body for the welcome email.";
    } else {
        try {
            $sql = "INSERT INTO welcome_emails (subject, body) VALUES (:subject, :body)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':subject' => $subject,
                ':body'    => $body
            ]);
            $message = "Welcome email template updated successfully.";
        } catch (PDOException $e) {
            $message = "Error updating template: " . $e->getMessage();
        }
    }
}

// Retrieve the latest welcome email template for display
$currentTemplate = ['subject' => '', 'body' => ''];
try {
    $stmt = $pdo->query("SELECT subject, body, created_at FROM welcome_emails ORDER BY created_at DESC LIMIT 1");
    $currentTemplate = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$currentTemplate) {
        $currentTemplate = ['subject' => '', 'body' => ''];
    }
} catch (PDOException $e) {
    // Handle error if needed
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Welcome Email</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background-color: black; color: white; }
        .container { margin-top: 20px; }
        .card { background-color: #222; border: 1px solid #444; }
        .card-header { background-color: #333; }
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
    
    <!-- Edit Welcome Email Form -->
    <div class="container">
        <div class="card mb-4">
            <div class="card-header">
                <h2>Edit Welcome Email</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <form action="edit_welcome_email.php" method="POST">
                    <div class="mb-3">
                        <label for="subject" class="form-label">Email Subject</label>
                        <input type="text" name="subject" id="subject" class="form-control" value="<?php echo htmlspecialchars($currentTemplate['subject']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="body" class="form-label">Email Body</label>
                        <textarea name="body" id="body" class="form-control" rows="10" required><?php echo htmlspecialchars($currentTemplate['body']); ?></textarea>
                        <small class="form-text text-muted">Use placeholders like {name} and {team} for dynamic content.</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Welcome Email</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
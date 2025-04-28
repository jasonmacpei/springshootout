<?php
// merge_contacts.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: admin.php');
    exit;
}

// Include config and DB connection
require_once '/home/lostan6/springshootout.ca/includes/config.php';
require_once __DIR__ . '/../scripts/php/db_connect.php';

// Step 1: Find all emails that appear more than once
try {
    $duplicateEmailsSql = "
        SELECT email_address
        FROM contacts
        WHERE email_address IS NOT NULL
        GROUP BY email_address
        HAVING COUNT(*) > 1
    ";
    $dupStmt = $pdo->query($duplicateEmailsSql);
    $duplicateEmails = $dupStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    die("Error finding duplicate emails: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Merge Duplicate Contacts</title>
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
        .duplicate-group {
            margin-bottom: 40px;
        }
        table td, table th {
            color: white;
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

    <!-- Main Content -->
    <div class="container">
        <h1 class="mt-4">Merge Duplicate Contacts</h1>

        <?php if (empty($duplicateEmails)): ?>
            <div class="alert alert-success mt-4">
                No duplicate contacts found by email.
            </div>
        <?php else: ?>
            <div class="alert alert-info mt-4">
                Below are groups of contacts that share the same email address.
                Select one "master" contact per group and click "Merge Selected" to merge duplicates.
            </div>

            <form action="/scripts/php/merge_contacts_process.php" method="POST" style="margin-bottom: 60px;">
                <?php
                // Step 2: For each duplicate email, fetch the contacts
                foreach ($duplicateEmails as $email) {
                    // Fetch all contacts with this email
                    $contactsSql = "
                        SELECT contact_id, contact_name, email_address, phone_number
                        FROM contacts
                        WHERE email_address = :email
                        ORDER BY contact_name
                    ";
                    $contactsStmt = $pdo->prepare($contactsSql);
                    $contactsStmt->execute([':email' => $email]);
                    $contacts = $contactsStmt->fetchAll(PDO::FETCH_ASSOC);

                    if (count($contacts) < 2) {
                        continue;
                    }

                    // Sanitize the email into a safe group key
                    $groupKey = str_replace(['@','.', '+'], '_', $email);
                    ?>
                    <div class="duplicate-group">
                        <h3>Email: <?php echo htmlspecialchars($email); ?></h3>
                        <table class="table table-dark table-striped">
                            <thead>
                                <tr>
                                    <th>Choose Master</th>
                                    <th>Contact ID</th>
                                    <th>Contact Name</th>
                                    <th>Phone Number</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($contacts as $c): ?>
                                    <tr>
                                        <td>
                                            <input type="radio" 
                                                   name="master_for_<?php echo $groupKey; ?>"
                                                   value="<?php echo htmlspecialchars($c['contact_id']); ?>"
                                                   required>
                                        </td>
                                        <td><?php echo htmlspecialchars($c['contact_id']); ?></td>
                                        <td><?php echo htmlspecialchars($c['contact_name']); ?></td>
                                        <td><?php echo htmlspecialchars($c['phone_number']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php
                        $contactIds = array_column($contacts, 'contact_id');
                        ?>
                        <input type="hidden" name="duplicate_groups[<?php echo $groupKey; ?>]" 
                               value="<?php echo implode(',', $contactIds); ?>">
                    </div>
                    <?php
                }
                ?>
                <button type="submit" class="btn btn-primary">Merge Selected</button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" 
            integrity="sha384-..." crossorigin="anonymous"></script>
</body>
</html>
<?php
// send_email.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// For pages, use this path:
require_once '../scripts/php/db_connect.php';
require_once '/home/lostan6/springshootout.ca/includes/config.php';
// Load PHPMailer via Composer autoload
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$messageType = ''; // 'success' or 'danger'
$emailRecipients = []; // Track all recipients for display

// Process the form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Combine recipients from multiple sources into an array
    $recipients = array();

    // 1. Manual email addresses (comma-separated)
    if (!empty($_POST['manual_to'])) {
        $manualEmails = explode(',', $_POST['manual_to']);
        foreach ($manualEmails as $email) {
            $trimmed = trim($email);
            if (!empty($trimmed)) {
                $recipients[] = $trimmed;
            }
        }
    }

    // 2. Selected contacts (multiple select)
    if (!empty($_POST['selected_contacts']) && is_array($_POST['selected_contacts'])) {
        $ids = implode(',', array_map('intval', $_POST['selected_contacts']));
        $sql = "SELECT contact_name, email_address FROM contacts WHERE contact_id IN ($ids)";
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            if (!empty($row['email_address'])) {
                $recipients[] = $row['email_address'];
                $emailRecipients[] = $row['contact_name'] . ' (' . $row['email_address'] . ')';
            }
        }
    }

    // 3. Selected group (role) OR "All Contacts"
    if (!empty($_POST['selected_group'])) {
        if ($_POST['selected_group'] === 'all') {
            $sql = "SELECT contact_name, email_address FROM contacts WHERE email_address IS NOT NULL AND email_address != ''";
            $stmt = $pdo->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                if (!empty($row['email_address'])) {
                    $recipients[] = $row['email_address'];
                    $emailRecipients[] = $row['contact_name'] . ' (' . $row['email_address'] . ')';
                }
            }
        } else {
            $roleId = (int)$_POST['selected_group'];
            $sql = "SELECT contact_name, email_address FROM contacts WHERE role_id = :role_id AND email_address IS NOT NULL AND email_address != ''";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':role_id' => $roleId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                if (!empty($row['email_address'])) {
                    $recipients[] = $row['email_address'];
                    $emailRecipients[] = $row['contact_name'] . ' (' . $row['email_address'] . ')';
                }
            }
        }
    }

    // Remove duplicates while preserving display names
    $uniqueRecipients = [];
    $uniqueEmails = [];
    foreach ($emailRecipients as $key => $recipient) {
        $email = $recipients[$key];
        if (!in_array($email, $uniqueEmails)) {
            $uniqueEmails[] = $email;
            $uniqueRecipients[] = $recipient;
        }
    }
    $emailRecipients = $uniqueRecipients;

    // Remove duplicates from email addresses
    $recipients = array_unique($recipients);
    $recipientCount = count($recipients);

    // Get subject and body from the form
    $subject = trim($_POST['subject']);
    $body = trim($_POST['body']);

    // Create a plain-text version of the body by stripping HTML tags
    $plainBody = strip_tags($body);

    if (empty($recipients) || empty($subject) || empty($body)) {
        $message = "Please provide a recipient, subject, and message body.";
        $messageType = 'danger';
    } else {
        $mail = new PHPMailer(true);
        try {
            // SMTP settings
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            
            // Add detailed debug info to log (not visible to users)
            $mail->SMTPDebug = 2;  // Debug level
            $mail->Debugoutput = function($str, $level) {
                error_log("PHPMailer Debug: $str");
            };
            
            // Set generous timeout
            $mail->Timeout = 30;
            
            // Try SMTPS on port 465 (most common for shared hosting)
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = SMTP_PORT;
            
            // Set connection options to be more tolerant
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // Set "To" always as admin email and add all recipients as BCC
            $mail->setFrom(SMTP_USER, SITE_NAME);
            $mail->addAddress(ADMIN_EMAIL); // Primary "To" address
            foreach ($recipients as $recipient) {
                $mail->addBCC($recipient);
            }

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = $plainBody;

            $mail->send();

            // Prepare a comma-separated string of recipients for logging
            $recipientsStr = implode(', ', $recipients);

            // Insert email details into the sent_emails table using the plain text version of the body
            $sqlInsert = "INSERT INTO sent_emails (recipient, subject, body) VALUES (:recipient, :subject, :body)";
            $stmtInsert = $pdo->prepare($sqlInsert);
            $stmtInsert->execute([
                ':recipient' => $recipientsStr,
                ':subject'   => $subject,
                ':body'      => $plainBody
            ]);

            $message = "<strong>Email sent successfully!</strong><br>
                        • Total recipients: {$recipientCount}<br>
                        • Subject: " . htmlspecialchars($subject);
            $messageType = 'success';
        } catch (Exception $e) {
            $message = "<strong>Message could not be sent.</strong><br>
                       <strong>Mailer Error:</strong> " . $mail->ErrorInfo . "<br>
                       <strong>Details:</strong> Please check your SMTP settings or contact your administrator.";
            $messageType = 'danger';
            error_log("Email sending error: " . $mail->ErrorInfo);
        }
    }
}

// Fetch contacts for the multi-select dropdown
try {
    $stmtContacts = $pdo->query("SELECT contact_id, contact_name, email_address FROM contacts ORDER BY contact_name");
    $contacts = $stmtContacts->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $contacts = [];
}

// Fetch roles from the contact_roles table
try {
    $stmtRoles = $pdo->query("SELECT role_id, role_name FROM contact_roles ORDER BY role_name");
    $roles = $stmtRoles->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $roles = [];
}

// Fetch sent emails for display
try {
    $stmtEmails = $pdo->query("SELECT * FROM sent_emails ORDER BY sent_at DESC");
    $sentEmails = $stmtEmails->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $sentEmails = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Send Email</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-..." crossorigin="anonymous">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- TinyMCE Editor with your API key -->
    <script src="https://cdn.tiny.cloud/1/fxvl0auoface3uh9nwunptjtxdgl1ghrrc6zcegbglt1waac/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
    tinymce.init({
        selector: '#body',
        menubar: false,
        plugins: 'lists link image preview',
        toolbar: 'undo redo | formatselect | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | preview',
        height: 300,
        setup: function (editor) {
            document.querySelector('form').addEventListener('submit', function(e) {
                tinymce.triggerSave();
            });
        }
    });
    </script>
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
        #recipients-list {
            max-height: 200px;
            overflow-y: auto;
            background-color: #333;
            border-radius: 5px;
            padding: 10px;
            margin-top: 10px;
        }
        #recipients-list .recipient-item {
            padding: 4px;
            border-bottom: 1px solid #444;
        }
        #recipients-list .recipient-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <!-- Navbar: using PHP include for consistent styling -->
    <div class="container">
      <nav class="navbar navbar-expand-lg navbar-dark bg-dark justify-content-center">
        <div id="nav-placeholder">
          <?php include('../includes/navbar.html'); ?>
        </div>
      </nav>
    </div>

    <!-- Main Container -->
    <div class="container">
        <!-- Email Form -->
        <div class="row">
            <div class="col-md-12">
                <div class="card mb-4">
                    <div class="card-header">
                        <h2>Send Email</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?php echo $messageType; ?>">
                                <?php echo $message; ?>
                                
                                <?php if ($messageType === 'success' && !empty($emailRecipients)): ?>
                                    <div class="mt-3">
                                        <button class="btn btn-sm btn-outline-light" type="button" data-bs-toggle="collapse" data-bs-target="#recipientsCollapse">
                                            Show Recipients (<?php echo count($emailRecipients); ?>)
                                        </button>
                                        <div class="collapse mt-2" id="recipientsCollapse">
                                            <div id="recipients-list">
                                                <?php foreach ($emailRecipients as $recipient): ?>
                                                    <div class="recipient-item"><?php echo htmlspecialchars($recipient); ?></div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <form action="send_email.php" method="POST">
                            <!-- Option 1: Manual Email Addresses -->
                            <div class="form-group">
                                <label for="manual_to">Manual Email Addresses</label>
                                <input type="text" class="form-control" name="manual_to" id="manual_to" placeholder="Enter email addresses separated by commas">
                            </div>
                            <!-- Option 2: Select Contacts -->
                            <div class="form-group mt-3">
                                <label for="selected_contacts">Select Contacts</label>
                                <select name="selected_contacts[]" id="selected_contacts" class="form-control" multiple>
                                    <?php foreach ($contacts as $contact): ?>
                                        <option value="<?php echo htmlspecialchars($contact['contact_id']); ?>">
                                            <?php echo htmlspecialchars($contact['contact_name'] . ' (' . $contact['email_address'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">Hold Ctrl (or Cmd) to select multiple contacts.</small>
                            </div>
                            <!-- Option 3: Select a Contact Group or All Contacts -->
                            <div class="form-group mt-3">
                                <label for="selected_group">Select Group</label>
                                <select name="selected_group" id="selected_group" class="form-control">
                                    <option value="">-- Choose a group (e.g., Head Coaches) --</option>
                                    <option value="all">All Contacts</option>
                                    <?php foreach ($roles as $roleRow): ?>
                                        <option value="<?php echo htmlspecialchars($roleRow['role_id']); ?>">
                                            <?php echo htmlspecialchars($roleRow['role_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- Subject -->
                            <div class="form-group mt-3">
                                <label for="subject">Subject</label>
                                <input type="text" class="form-control" name="subject" id="subject" required>
                            </div>
                            <!-- Message Body with TinyMCE (no required attribute) -->
                            <div class="form-group mt-3">
                                <label for="body">Message</label>
                                <textarea class="form-control" name="body" id="body" rows="6"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary mt-4">Send Email</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sent Emails Table -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h2>Sent Emails</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($sentEmails)): ?>
                            <table class="table table-dark table-striped">
                                <thead>
                                    <tr>
                                        <th>Email ID</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>To</th>
                                        <th>Subject</th>
                                        <th>Preview</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sentEmails as $email):
                                        $dt = new DateTime($email['sent_at']);
                                        $date = $dt->format('Y-m-d');
                                        $time = $dt->format('H:i:s');
                                        $preview = substr($email['body'], 0, 100);
                                    ?>
                                        <tr>
                                            <td>
                                                <a href="email_detail.php?email_id=<?php echo $email['email_id']; ?>" class="text-light">
                                                    <?php echo $email['email_id']; ?>
                                                </a>
                                            </td>
                                            <td><?php echo $date; ?></td>
                                            <td><?php echo $time; ?></td>
                                            <td><?php echo htmlspecialchars($email['recipient']); ?></td>
                                            <td><?php echo htmlspecialchars($email['subject']); ?></td>
                                            <td><?php echo htmlspecialchars($preview); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>No emails have been sent yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" 
            integrity="sha384-..." crossorigin="anonymous"></script>
</body>
</html>
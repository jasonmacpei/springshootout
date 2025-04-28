<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// echo __DIR__;
// exit;

// Include config and DB connection (paths may differ in your environment)
require_once '/home/lostan6/springshootout.ca/includes/config.php';
// Or same-folder relative path:
require_once __DIR__ . '/db_connect.php';

// Make sure it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contact_id = isset($_POST['contact_id']) ? $_POST['contact_id'] : null;

    if ($contact_id) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM contacts WHERE contact_id = :contact_id");
            $stmt->bindParam(':contact_id', $contact_id, PDO::PARAM_INT);
            $stmt->execute();
            $contact = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($contact) {
                // Return an HTML form that the main page can inject
                // into the #contactForm div
                ?>
                <form id="updateContactForm">
                    <input type="hidden" name="contact_id" value="<?php echo htmlspecialchars($contact['contact_id']); ?>">

                    <div class="form-group">
                        <label for="contact_name">Contact Name</label>
                        <input type="text" name="contact_name" id="contact_name" class="form-control"
                               value="<?php echo htmlspecialchars($contact['contact_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email_address">Email Address</label>
                        <input type="email" name="email_address" id="email_address" class="form-control"
                               value="<?php echo htmlspecialchars($contact['email_address']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="phone_number">Phone Number</label>
                        <input type="text" name="phone_number" id="phone_number" class="form-control"
                               value="<?php echo htmlspecialchars($contact['phone_number']); ?>">
                    </div>

                    <button type="submit" class="btn btn-primary mt-3">Update Contact</button>
                </form>
                <?php
            } else {
                echo "No contact found with ID $contact_id";
            }
        } catch (PDOException $e) {
            echo "Error fetching contact: " . $e->getMessage();
        }
    } else {
        echo "No contact ID provided.";
    }
} else {
    echo "Invalid request method.";
}
?>
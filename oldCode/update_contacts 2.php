<?php
// update_contacts.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include configuration and DB connection
require_once '/home/lostan6/springshootout.ca/includes/config.php';
require_once __DIR__ . '/../scripts/php/db_connect.php';

// Fetch contacts from the database for the dropdown
try {
    $sql = "SELECT contact_id, contact_name FROM contacts ORDER BY contact_name";
    $stmt = $pdo->query($sql);
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching contacts: " . $e->getMessage();
    $contacts = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Contact</title>
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
        /* Card styling to match other pages */
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
                <!-- If your navbar.html has the fully formed nav items, just include it: -->
                <?php include('../includes/navbar.html'); ?>
            </div>
        </nav>
    </div>

    <!-- Main Content Container -->
    <div class="container">
        <div class="row mb-4">
            <div class="col text-center">
                <h1>Update Contact</h1>
            </div>
            <div class="col text-end">
                <a href="list_contacts.php" class="btn btn-primary">Contacts List</a>
            </div>
        </div>

        <!-- Dropdown for selecting a contact -->
        <select id="contactSelect" class="form-control mb-4">
            <option value="">Select a Contact</option>
            <?php foreach ($contacts as $contact): ?>
                <option value="<?php echo htmlspecialchars($contact['contact_id']); ?>">
                    <?php echo htmlspecialchars($contact['contact_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- Placeholder for the update form (loaded via AJAX) -->
        <div id="contactForm"></div>

        <!-- Delete button for the selected contact -->
        <button id="deleteButton" class="btn btn-danger mt-3" style="display:none;">Delete Contact</button>
    </div>

    <!-- Bootstrap 5 JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" 
            integrity="sha384-..." crossorigin="anonymous"></script>
    <!-- jQuery for AJAX -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function(){
            // When a contact is selected, load the update form via AJAX
            $('#contactSelect').change(function(){
                var contactId = $(this).val();
                if(contactId) {
                    $.ajax({
                        url: '/scripts/php/fetch_contact_data.php',
                        type: 'POST',
                        data: { contact_id: contactId },
                        success: function(response) {
                            $('#contactForm').html(response);
                            $('#deleteButton').show();
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            $('#contactForm').html('Error loading data: ' + errorThrown);
                        }
                    });
                } else {
                    $('#contactForm').empty();
                    $('#deleteButton').hide();
                }
            });

            // Delete button functionality
            $('#deleteButton').click(function(){
                var contactId = $('#contactSelect').val();
                if(contactId && confirm("Are you sure you want to delete this contact?")) {
                    $.ajax({
                        url: '/scripts/php/delete_contact_process.php',
                        type: 'POST',
                        data: { contact_id: contactId },
                        success: function(response) {
                            alert('Contact deleted successfully.');
                            // Remove deleted contact from dropdown
                            $("#contactSelect option[value='" + contactId + "']").remove();
                            $('#contactForm').empty();
                            $('#deleteButton').hide();
                        },
                        error: function(xhr, status, error) {
                            alert('Error deleting contact: ' + xhr.responseText);
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>
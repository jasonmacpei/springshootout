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
        /* Error message styling */
        .error-message {
            color: #ff6b6b;
            background-color: #2d1c1c;
            border: 1px solid #ff6b6b;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            display: none;
        }
        /* Team list in confirmation dialog */
        .team-list {
            background-color: #2d2d2d;
            border-radius: 4px;
            padding: 10px;
            max-height: 200px;
            overflow-y: auto;
            margin: 10px 0;
        }
        .team-list-item {
            padding: 5px;
            border-bottom: 1px solid #444;
        }
        .team-list-item:last-child {
            border-bottom: none;
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

        <!-- Error message container -->
        <div id="errorMessage" class="error-message"></div>

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
        
        <!-- Force Delete Modal -->
        <div class="modal fade" id="forceDeleteModal" tabindex="-1" aria-labelledby="forceDeleteModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content bg-dark text-white">
                    <div class="modal-header">
                        <h5 class="modal-title" id="forceDeleteModalLabel">Contact Has Team Associations</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p id="modalMessage"></p>
                        <div id="teamsList" class="team-list"></div>
                        <p class="text-warning">Warning: This will remove all team associations for this contact and then delete the contact.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="forceDeleteButton">Delete Anyway</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" 
            integrity="sha384-..." crossorigin="anonymous"></script>
    <!-- jQuery for AJAX -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function(){
            // Initialize modal
            var forceDeleteModal = new bootstrap.Modal(document.getElementById('forceDeleteModal'));
            var currentContactId = null;
            
            // Function to show error message
            function showError(message) {
                $('#errorMessage').html(message).show();
                // Auto-hide after 10 seconds
                setTimeout(function() {
                    $('#errorMessage').hide();
                }, 10000);
            }
            
            // Function to show success message
            function showSuccess(message) {
                $('#errorMessage').removeClass('error-message')
                     .addClass('alert alert-success')
                     .html(message)
                     .show();
                setTimeout(function() {
                    $('#errorMessage').hide();
                }, 5000);
            }
            
            // When a contact is selected, load the update form via AJAX
            $('#contactSelect').change(function(){
                var contactId = $(this).val();
                currentContactId = contactId;
                
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
                            showError('Error loading data: ' + jqXHR.responseText);
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
                    deleteContact(contactId, false);
                }
            });
            
            // Function to handle the contact deletion
            function deleteContact(contactId, forceDelete) {
                var data = { contact_id: contactId };
                if (forceDelete) {
                    data.force_delete = true;
                }
                
                $.ajax({
                    url: '/scripts/php/delete_contact_process.php',
                    type: 'POST',
                    data: data,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showSuccess(response.message);
                            // Remove deleted contact from dropdown
                            $("#contactSelect option[value='" + contactId + "']").remove();
                            $('#contactForm').empty();
                            $('#deleteButton').hide();
                        } else {
                            showError(response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        // Check if the error is due to team associations
                        if (xhr.status === 409) {
                            try {
                                var response = JSON.parse(xhr.responseText);
                                
                                // Populate the modal with team information
                                $('#modalMessage').text(response.message);
                                
                                // Build the teams list
                                var teamsHtml = '';
                                response.teams.forEach(function(team) {
                                    teamsHtml += '<div class="team-list-item">' + team.team_name + '</div>';
                                });
                                $('#teamsList').html(teamsHtml);
                                
                                // Show the modal
                                forceDeleteModal.show();
                            } catch (e) {
                                showError('Error processing response: ' + xhr.responseText);
                            }
                        } else {
                            showError('Error deleting contact: ' + xhr.responseText);
                        }
                    }
                });
            }
            
            // Force delete button handler
            $('#forceDeleteButton').click(function() {
                forceDeleteModal.hide();
                if (currentContactId) {
                    deleteContact(currentContactId, true);
                }
            });
            
            // Handle the contact update form submission via AJAX
            $(document).on('submit', '#updateContactForm', function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: '/scripts/php/update_contact_process.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        showSuccess('Contact updated successfully.');
                        // Update the contact name in the dropdown
                        var contactId = $('#updateContactForm input[name="contact_id"]').val();
                        var contactName = $('#updateContactForm input[name="contact_name"]').val();
                        $("#contactSelect option[value='" + contactId + "']").text(contactName);
                    },
                    error: function(xhr, status, error) {
                        showError('Error updating contact: ' + xhr.responseText);
                    }
                });
            });
        });
    </script>
</body>
</html>
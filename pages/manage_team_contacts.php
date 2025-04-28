<?php
// manage_team_contacts.php
// Allows an admin to manage contacts assigned to teams.
// Now loads roles dynamically from contact_roles and uses a dropdown for the "role" field.

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: admin.php');
    exit;
}

require_once '/home/lostan6/springshootout.ca/includes/config.php';
require_once __DIR__ . '/../scripts/php/db_connect.php';

// Fetch teams for the dropdown
try {
    $teamsStmt = $pdo->query("SELECT team_id, team_name FROM teams ORDER BY team_name");
    $teams = $teamsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching teams: " . $e->getMessage());
}

// Fetch all contacts for the "Add Contact" dropdown
try {
    $contactsStmt = $pdo->query("SELECT contact_id, contact_name, email_address FROM contacts ORDER BY contact_name");
    $allContacts = $contactsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching contacts: " . $e->getMessage());
}

// Fetch all roles from contact_roles (so we can store the role in the bridging table)
try {
    $rolesStmt = $pdo->query("SELECT role_id, role_name FROM contact_roles ORDER BY role_name");
    $allRoles = $rolesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching contact roles: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Team Contacts</title>
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
        .form-label,
        .form-text {
            color: white !important;
        }
        .form-control {
            background-color: #333 !important;
            color: white !important;
            border: 1px solid #555;
        }
    </style>
</head>
<body>
    <!-- Navbar container -->
    <div class="container">
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark justify-content-center">
            <div id="nav-placeholder">
                <?php include('../includes/navbar.html'); ?>
            </div>
        </nav>
    </div>

    <div class="container">
        <div class="card mb-4">
            <div class="card-header">
                <h2>Manage Team Contacts</h2>
            </div>
            <div class="card-body">
                <!-- Team Selection -->
                <div class="mb-3">
                    <label for="teamSelect" class="form-label">Select a Team</label>
                    <select id="teamSelect" class="form-control">
                        <option value="">Select a Team</option>
                        <?php foreach ($teams as $team): ?>
                            <option value="<?php echo htmlspecialchars($team['team_id']); ?>">
                                <?php echo htmlspecialchars($team['team_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted">
                        Choose the team you want to manage contacts for.
                    </small>
                </div>

                <!-- AJAX-loaded area for the team's current contacts -->
                <div id="teamContacts">
                    <!-- The list of team contacts will be loaded here via AJAX -->
                </div>

                <!-- Form to link a new contact to the selected team -->
                <div class="mt-4">
                    <h4>Link a Contact to the Team</h4>
                    <form id="linkContactForm">
                        <!-- Hidden field to hold the selected team ID -->
                        <input type="hidden" name="team_id" id="hiddenTeamId" value="">

                        <!-- Select Contact -->
                        <div class="mb-3">
                            <label for="contactSelect" class="form-label">Select Contact</label>
                            <select name="contact_id" id="contactSelect" class="form-control" required>
                                <option value="">Select a Contact</option>
                                <?php foreach ($allContacts as $contact): ?>
                                    <option value="<?php echo htmlspecialchars($contact['contact_id']); ?>">
                                        <?php echo htmlspecialchars($contact['contact_name'] . ' (' . $contact['email_address'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">
                                Choose the contact you wish to assign to the selected team.
                            </small>
                        </div>

                        <!-- Select Role (loaded from contact_roles table) -->
                        <div class="mb-3">
                            <label for="contactRoleSelect" class="form-label">Contact Role for the Team</label>
                            <select name="contact_role" id="contactRoleSelect" class="form-control">
                                <option value="">-- No Role Selected --</option>
                                <?php foreach ($allRoles as $role): ?>
                                    <option value="<?php echo htmlspecialchars($role['role_id']); ?>">
                                        <?php echo htmlspecialchars($role['role_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">
                                Choose the role this contact will have for the team (optional).
                            </small>
                        </div>

                        <button type="submit" class="btn btn-primary">Link Contact to Team</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS + jQuery for AJAX -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function(){
        // When a team is selected, store ID and load existing contacts
        $('#teamSelect').change(function(){
            var teamId = $(this).val();
            $('#hiddenTeamId').val(teamId);
            if (teamId) {
                $.ajax({
                    url: '/scripts/php/fetch_team_contacts.php',
                    type: 'POST',
                    data: { team_id: teamId },
                    success: function(response) {
                        $('#teamContacts').html(response);
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        $('#teamContacts').html('<div class="alert alert-danger">Error loading team contacts: ' + errorThrown + '</div>');
                    }
                });
            } else {
                $('#teamContacts').empty();
            }
        });

        // Link a new contact to the selected team
        $('#linkContactForm').submit(function(e){
            e.preventDefault();
            var formData = $(this).serialize();
            $.ajax({
                url: '/scripts/php/link_team_contact_process.php',
                type: 'POST',
                data: formData,
                success: function(response) {
                    alert(response);
                    var teamId = $('#hiddenTeamId').val();
                    if (teamId) {
                        // Reload the team's contacts
                        $.ajax({
                            url: '/scripts/php/fetch_team_contacts.php',
                            type: 'POST',
                            data: { team_id: teamId },
                            success: function(resp) {
                                $('#teamContacts').html(resp);
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                $('#teamContacts').html('<div class="alert alert-danger">Error reloading team contacts: ' + errorThrown + '</div>');
                            }
                        });
                    }
                },
                error: function(xhr, status, error) {
                    alert('Error linking contact: ' + xhr.responseText);
                }
            });
        });

        // Removing a contact link is handled in fetch_team_contacts.php's output 
        // (a .delete-contact-link button). We'll attach an event handler:
        $(document).on('click', '.delete-contact-link', function(){
            var teamId = $(this).data('team-id');
            var contactId = $(this).data('contact-id');
            if (teamId && contactId && confirm("Are you sure you want to remove this contact from the team?")) {
                $.ajax({
                    url: '/scripts/php/link_team_contact_process.php',
                    type: 'POST',
                    data: {
                        action: 'remove',
                        team_id: teamId,
                        contact_id: contactId
                    },
                    success: function(response) {
                        alert(response);
                        // Reload the team's contacts
                        $.ajax({
                            url: '/scripts/php/fetch_team_contacts.php',
                            type: 'POST',
                            data: { team_id: teamId },
                            success: function(resp) {
                                $('#teamContacts').html(resp);
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                $('#teamContacts').html('<div class="alert alert-danger">Error reloading team contacts: ' + errorThrown + '</div>');
                            }
                        });
                    },
                    error: function(xhr, status, error) {
                        alert('Error removing contact: ' + xhr.responseText);
                    }
                });
            }
        });
    });
    </script>
</body>
</html>
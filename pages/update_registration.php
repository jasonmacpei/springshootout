<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the configuration file which sets the error log path
require_once '/home/lostan6/springshootout.ca/includes/config.php';

// Ensure you have the correct path to the db_connect.php file.
require __DIR__ . '/../scripts/php/db_connect.php'; 
// Include the configuration file which sets the error log path
require_once '/home/lostan6/springshootout.ca/includes/config.php';

// Fetch teams from the database to populate the dropdown.
try {
    $teamQuery = "
    SELECT 
        registration_id as registration_id,
        r.team_id as team_id,
        t.team_name as team_name
    FROM registrations r 
    JOIN teams t ON t.team_id = r.team_id
    WHERE year IN (2024, 2025, 2026)
    ORDER BY team_name
    ";
    $stmt = $pdo->query($teamQuery);
    $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching teams: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Registration</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background-color: black;
            color: white;
        }
        .topPart {
            display: flex;
        }
        .itemHeading {
            flex: 80%
        }
        .itemButton {
            flex: 20%
        }
    </style>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
</head>
<body>

<div class="container">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark justify-content-center">
        <div id="nav-placeholder"></div>
    </nav>

    <div class="text-center">
        <img src="../assets/images/name.png" alt="Spring Shootout">
    </div>

    <div class="container">
        <div class="row">
            <div class="col" style="flex: 0 0 80%;">
                <h1 class="text-center mb-4">Update Registration</h1>
            </div>
            <div class="col" style="flex: 0 0 20%;">
                <a href="./list_teams.php" class="btn btn-primary">Teams</a>
            </div>
        </div>
        <div id="successAlert" class="alert alert-success" role="alert" style="display:none;">
            Update was successful. Choose another team to update if you wish.
        </div>    

        <!-- Dropdown for selecting a team -->
        <select id="teamSelect" class="form-control mb-4">
            <option value="">Select a Team</option>
            <?php foreach ($teams as $team): ?>
                <option value="<?php echo htmlspecialchars($team['registration_id']); ?>">
                    <?php echo htmlspecialchars($team['team_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- Form will be populated with AJAX -->
        <div id="registrationForm">
            <!-- The form fields will be loaded here based on the selected team -->
        </div>

        <!-- Delete button for removing a team -->
        <button id="deleteButton" class="btn btn-danger mt-3" style="display:none;">Delete Team</button>
    </div>
</div>

<!-- jQuery and Bootstrap JS -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<!-- Navbar dynamic loading -->
<script>
    $(document).ready(function() {
        $("#nav-placeholder").load("../includes/navbar.html");

        // Bind change event to load team data and toggle the delete button.
        $('#teamSelect').change(function() {
            var registrationId = $(this).val();
            if (registrationId) {
                loadTeamData(registrationId);
                $('#deleteButton').show();
            } else {
                $('#registrationForm').empty();
                $('#deleteButton').hide();
            }
        });

        // Function to load team data via AJAX.
        function loadTeamData(registrationId) {
            $.ajax({
                url: '/scripts/php/fetch_team_data.php', // Ensure this path is correct
                type: 'POST',
                data: { registration_id: registrationId },
                success: function(response) {
                    $('#registrationForm').html(response);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    $('#registrationForm').html('Error loading data: ' + errorThrown);
                }
            });
        }

        // Handle update registration form submission.
        $(document).on('submit', '#updateRegistrationForm', function(e) {
            e.preventDefault();
            var formData = $(this).serialize();
            $.ajax({
                url: '/scripts/php/update_registration_process.php', // Ensure this path is correct
                type: 'POST',
                data: formData,
                success: function(response) {
                    $('#successAlert').show();
                    $('#updateRegistrationForm').hide();
                },
                error: function(xhr, status, error) {
                    alert('An error occurred: ' + xhr.responseText);
                }
            });
        });

        // Handle deletion of a team registration.
        $('#deleteButton').click(function() {
            var registrationId = $('#teamSelect').val();
            if (registrationId && confirm("Are you sure you want to delete this registration?")) {
                $.ajax({
                    url: '/scripts/php/delete_registration_process.php', // Ensure this path and endpoint exist
                    type: 'POST',
                    data: { registration_id: registrationId },
                    success: function(response) {
                        alert('Registration deleted successfully.');
                        // Remove the deleted team from the dropdown.
                        $("#teamSelect option[value='" + registrationId + "']").remove();
                        // Clear the form and hide the delete button.
                        $('#registrationForm').empty();
                        $('#deleteButton').hide();
                        $('#successAlert').hide();
                    },
                    error: function(xhr, status, error) {
                        alert('Error deleting registration: ' + xhr.responseText);
                    }
                });
            }
        });
    });
</script>
</body>
</html>
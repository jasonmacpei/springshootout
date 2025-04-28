<?php
// registration.php
// This page displays the registration form and loads required data.

// Include configuration which handles error reporting settings
if (file_exists('/home/lostan6/springshootout.ca/includes/config.php')) {
    require_once '/home/lostan6/springshootout.ca/includes/config.php';
} else {
    require_once __DIR__ . '/../includes/config.php';
}

// For pages, use these paths:
require_once __DIR__ . '/../scripts/php/db_connect.php';
// Load PHPMailer via Composer autoload
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Initialize variables
$errors = [];
$formSubmitted = false;
$registrationSuccess = false;

// Fetch contact roles from the database so we can build a dropdown
$contactRoles = [];
try {
    $roleSql = "SELECT role_id, role_name FROM contact_roles ORDER BY role_name";
    $stmtRole = $pdo->query($roleSql);
    $contactRoles = $stmtRole->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching contact roles: " . $e->getMessage());
    $errors[] = "Unable to load contact roles. Please try again later.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spring Shootout - Registration</title>
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
            background-color: #222;
            border: 1px solid #444;
        }
        .card-header {
            background-color: #333;
        }
        .form-label, .form-text {
            color: white !important;
        }
        .form-control {
            background-color: #333 !important;
            color: white !important;
            border: 1px solid #555;
        }
        .form-control::placeholder {
            color: #bbb;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="container">
      <nav class="navbar navbar-expand-lg navbar-dark bg-dark justify-content-center">
          <div id="nav-placeholder"></div>
      </nav>
    </div>
    
    <!-- Registration Form -->
    <div class="container">
        <?php if ($formSubmitted && !empty($errors)): ?>
            <div class="alert alert-danger">
                <h4 class="alert-heading">Please fix the following errors:</h4>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2>Registration v2</h2>
            </div>
            <div class="card-body">
                <!-- Note: The form action now points to register.php -->
                <form action="/scripts/php/register.php" method="POST" novalidate>
                    <div class="mb-3">
                        <label for="team_name" class="form-label">Team Name:</label>
                        <input type="text" name="team_name" id="team_name" class="form-control" required 
                               placeholder="Enter team name" value="<?php echo htmlspecialchars($_POST['team_name'] ?? ''); ?>">
                        <small class="form-text text-muted">Enter the name of your team.</small>
                    </div>
                    <div class="mb-3">
                        <label for="contact_name" class="form-label">Contact Name:</label>
                        <input type="text" name="contact_name" id="contact_name" class="form-control" required 
                               placeholder="Full name" value="<?php echo htmlspecialchars($_POST['contact_name'] ?? ''); ?>">
                        <small class="form-text text-muted">Enter the primary contact's full name.</small>
                    </div>
                    <div class="mb-3">
                        <label for="contact_role" class="form-label">Contact Role:</label>
                        <select name="contact_role" id="contact_role" class="form-control" required>
                            <option value="">-- Select Role --</option>
                            <?php 
                            $selectedRole = $_POST['contact_role'] ?? '';
                            foreach ($contactRoles as $role): 
                                $isSelected = ($selectedRole == $role['role_id']) ? 'selected' : '';
                            ?>
                                <option value="<?php echo htmlspecialchars($role['role_id']); ?>" <?php echo $isSelected; ?>>
                                    <?php echo htmlspecialchars($role['role_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Select the contact's role (e.g., Head Coach, Manager) from the list.</small>
                    </div>
                    <div class="mb-3">
                        <label for="province" class="form-label">Province:</label>
                        <input type="text" name="province" id="province" class="form-control" required 
                               placeholder="e.g., Ontario" value="<?php echo htmlspecialchars($_POST['province'] ?? ''); ?>">
                        <small class="form-text text-muted">Enter the province where your team is located.</small>
                    </div>
                    <div class="mb-3">
                        <label for="division" class="form-label">Division:</label>
                        <select name="division" id="division" class="form-control" required>
                            <option value="">-- Select Division --</option>
                            <?php 
                            $divisions = ['u11', 'u12', 'u13'];
                            $selectedDivision = $_POST['division'] ?? '';
                            foreach ($divisions as $div): 
                                $isSelected = ($selectedDivision == $div) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $div; ?>" <?php echo $isSelected; ?>><?php echo $div; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Select your team's age division.</small>
                    </div>
                    <div class="mb-3">
                        <label for="class" class="form-label">Class:</label>
                        <select name="class" id="class" class="form-control" required>
                            <option value="">-- Select Class --</option>
                            <?php 
                            $classes = ['Div 1', 'Div 2', 'Div 3', 'Div 4', 'Unknown'];
                            $selectedClass = $_POST['class'] ?? '';
                            foreach ($classes as $cls): 
                                $isSelected = ($selectedClass == $cls) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $cls; ?>" <?php echo $isSelected; ?>><?php echo $cls; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Select your team's competitive class.</small>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email:</label>
                        <input type="email" name="email" id="email" class="form-control" required 
                               placeholder="email@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        <small class="form-text text-muted">Enter the contact's email address.</small>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone:</label>
                        <input type="tel" name="phone" id="phone" class="form-control" required 
                               placeholder="e.g., (416) 555-1234" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        <small class="form-text text-muted">Enter the contact's phone number.</small>
                    </div>
                    <div class="mb-3">
                        <label for="note" class="form-label">Notes (Optional):</label>
                        <textarea name="note" id="note" class="form-control" rows="3" 
                                  placeholder="Any additional information"><?php echo htmlspecialchars($_POST['note'] ?? ''); ?></textarea>
                        <small class="form-text text-muted">Any special requests or additional information.</small>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-shootout-orange">Register Team</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- jQuery and Bootstrap JS -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
            crossorigin="anonymous"></script>
    <!-- Load the navbar dynamically -->
    <script>
        $(document).ready(function() {
            $("#nav-placeholder").load("/includes/navbar.html");
        });
    </script>
    
    <!-- Phone Number Formatting Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const phoneInput = document.getElementById('phone');
            
            phoneInput.addEventListener('input', function(e) {
                let digits = this.value.replace(/\D/g, '');
                digits = digits.substring(0, 10);
                let formattedNumber = '';
                if (digits.length > 0) {
                    formattedNumber = '(' + digits.substring(0, 3);
                    if (digits.length > 3) {
                        formattedNumber += ') ' + digits.substring(3, 6);
                        if (digits.length > 6) {
                            formattedNumber += '-' + digits.substring(6, 10);
                        }
                    }
                }
                this.value = formattedNumber;
            });
            
            if (phoneInput.value) {
                let event = new Event('input');
                phoneInput.dispatchEvent(event);
            }
        });
    </script>
</body>
</html>
<?php
// contacts.php
// This page lets users add additional contacts for an existing team.
require_once '../scripts/php/db_connect.php';

// Ensure a team_id is provided via GET.
if (!isset($_GET['team_id']) || !is_numeric($_GET['team_id'])) {
    die("Error: Team ID is missing or invalid.");
}
$team_id = $_GET['team_id'];

// Retrieve contact roles from the contact_roles table.
$roleQuery = "SELECT role_id, role_name FROM contact_roles ORDER BY role_name";
$roleStmt = $pdo->prepare($roleQuery);
$roleStmt->execute();
$roles = $roleStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Additional Contacts - Spring Shootout</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-aFq/bzH65dt+w6FI2ooMVUpc+21e0SRygnTpmBvdBgSdnuTN7QbdgL+OapgHtvPp" crossorigin="anonymous">
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    /* Additional styling if needed */
    .form-field {
        margin-bottom: 15px;
    }
    .form-field label {
        display: block;
        color: white;
        margin-bottom: 5px;
    }
    .form-field input, .form-field select {
        width: 100%;
        padding: 8px;
        box-sizing: border-box;
    }
  </style>
</head>
<body>
  <div class="container">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark justify-content-center">
      <div id="nav-placeholder">
        <!-- Navbar content loaded dynamically -->
      </div>
    </nav>
  </div>
  <div class="poster">
    <img src="../assets/images/name.png" alt="Spring Shootout">
  </div>
  <h1 class="text-center my-4">Add Additional Contact for Your Team</h1>
  <div class="container">
    <div class="row">
      <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1 col-sm-12">
        <form action="/scripts/php/add_contact.php" method="post">
          <!-- Hidden field for team_id -->
          <input type="hidden" name="team_id" value="<?php echo htmlspecialchars($team_id); ?>">
          <div class="form-field mb-3">
              <label for="contactName">Contact Name:</label>
              <input type="text" id="contactName" name="contactName" placeholder="Enter contact name" class="form-control" required>
          </div>
          <div class="form-field mb-3">
              <label for="email">Email Address:</label>
              <input type="email" id="email" name="email" placeholder="Enter email address" class="form-control" required>
          </div>
          <div class="form-field mb-3">
              <label for="phone">Phone Number:</label>
              <input type="tel" id="phone" name="phone" placeholder="Enter phone number (e.g., (416) 555-1234)" class="form-control" required>
          </div>
          <div class="form-field mb-3">
              <label for="role">Contact Role:</label>
              <select id="role" name="role" class="form-select" required>
                  <option value="">Select a Role</option>
                  <?php foreach ($roles as $role): ?>
                    <option value="<?php echo htmlspecialchars($role['role_id']); ?>">
                      <?php echo htmlspecialchars($role['role_name']); ?>
                    </option>
                  <?php endforeach; ?>
              </select>
          </div>
          <div class="form-field mb-3">
              <button type="submit" class="btn btn-primary">Add Contact</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <!-- Include jQuery and Bootstrap JS -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" 
          integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" 
          crossorigin="anonymous"></script>
  <script>
    $(document).ready(function() {
      $("#nav-placeholder").load("../includes/navbar.html");
    });
  </script>
  
  <!-- Phone Number Formatting Script -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
        const phoneInput = document.getElementById('phone');
        
        phoneInput.addEventListener('input', function(e) {
            // Get only the digits from the input
            let digits = this.value.replace(/\D/g, '');
            
            // Limit to 10 digits (North American phone number)
            digits = digits.substring(0, 10);
            
            // Format the phone number as the user types
            let formattedNumber = '';
            if (digits.length > 0) {
                // Format: (xxx)
                formattedNumber = '(' + digits.substring(0, 3);
                
                if (digits.length > 3) {
                    // Format: (xxx) xxx
                    formattedNumber += ') ' + digits.substring(3, 6);
                    
                    if (digits.length > 6) {
                        // Format: (xxx) xxx-xxxx
                        formattedNumber += '-' + digits.substring(6, 10);
                    }
                }
            }
            
            // Update the input value with the formatted number
            this.value = formattedNumber;
        });
        
        // Format any existing value when the page loads
        if (phoneInput.value) {
            let event = new Event('input');
            phoneInput.dispatchEvent(event);
        }
    });
  </script>
</body>
</html>

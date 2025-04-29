<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: admin.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Spring Shootout</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-aFq/bzH65dt+w6FI2ooMVUpc+21e0SRygnTpmBvdBgSdnuTN7QbdgL+OapgHtvPp" crossorigin="anonymous">
  <!-- Custom Stylesheet -->
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .white-border td, .white-border th {
              border: 1px solid white;
          }
  </style>
</head>

<body>

  <div class="container">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark justify-content-center">
      <div id="nav-placeholder"></div>
    </nav>
  </div>

  <!-- Content of the page -->
  <div class="poster">
    <img src="../assets/images/name.png" alt="Poster Name">
  </div>
  <div style="padding-top: 30px;">
    <hr>
  </div>
  <div class="container mt-3">
        <table class="table table-dark table-striped table-sm white-border">
            <thead>
                <tr>
                    <th>Page</th>
                    <th>Link</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>List of Teams</td>
                    <td><a href="./list_teams.php" class="btn btn-primary">Teams</a></td>
                </tr>
                <tr>
                    <td>List of Contacts</td>
                    <td><a href="./list_contacts.php" class="btn btn-primary">Contacts</a></td>
                </tr>

                <tr>
                    <td>Enter Additional Contacts</td>
                    <td><a href="./add_contact_admin.php" class="btn btn-primary">Enter Additional Contacts</a></td>
                </tr>

                <tr>
                    <td>Update Registrations</td>
                    <td><a href="./update_registration.php" class="btn btn-primary">Update Registrations</a></td>
                </tr>

                <tr>
                    <td>Update Welcome Email</td>
                    <td><a href="./edit_welcome_email.php" class="btn btn-primary">Update Welcome Email</a></td>
                </tr>

                <tr>
                    <td>Update Contacts</td>
                    <td><a href="./update_contacts.php" class="btn btn-primary">Update Contacts</a></td>
                </tr>
                <tr>
                    <td>Enter Schedule</td>
                    <td><a href="./make_schedule.php" class="btn btn-primary">Make Schedule</a></td>
                </tr>
                <tr>
                    <td>View Schedule</td>
                    <td><a href="./schedule.php" class="btn btn-primary">View Schedule</a></td>
                </tr>
                <tr>
                    <td>Edit Schedule</td>
                    <td><a href="./edit_schedule.php" class="btn btn-primary">Edit Schedule</a></td>
                </tr>
                <tr>
                    <td>Enter Results</td>
                    <td><a href="./enter_results.php" class="btn btn-primary">Enter Results</a></td>
                </tr>
                <tr>
                    <td>Edit Results</td>
                    <td><a href="./edit_results.php" class="btn btn-primary">Edit Results</a></td>
                </tr>
                <tr>
                    <td>Manage Pools</td>
                    <td><a href="./manage_pools.php" class="btn btn-primary">Manage Pools</a></td>
                </tr>
                <tr>
                    <td>Assign Teams to Pools</td>
                    <td><a href="./assign_pools.php" class="btn btn-primary">Assign Pools</a></td>
                </tr>
                <tr>
                    <td>Enter Uniforms</td>
                    <td><a href="./uniform.php" class="btn btn-primary">Enter Uniforms</a></td>
                </tr>
                <tr>
                    <td>Send Email</td>
                    <td><a href="./send_email.php" class="btn btn-primary">Send Email</a></td>
                </tr>

                <tr>
                    <td>Merge Duplicate Contacts</td>
                    <td><a href="./merge_contacts.php" class="btn btn-primary">Merge Duplicate Contacts</a></td>
                </tr>

                <tr>
                    <td>Manage Team Contacts</td>
                    <td><a href="./manage_team_contacts.php" class="btn btn-primary">Manage Team Contacts</a></td>
                </tr>

                <tr>
                    <td>Logout</td>
                    <td><a href="../scripts/php/logout.php" class="btn btn-danger">Logout</a></td>
                </tr>
            </tbody>
        </table>
    </div>


  <!-- jQuery and Bootstrap JS -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  <!-- Navbar dynamic loading -->
  <script>
    $(document).ready(function() {
        $("#nav-placeholder").load("../includes/navbar.html");
    });
  </script>

</body>
</html>

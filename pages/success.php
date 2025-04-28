<?php
// success.php
$teamId = isset($_GET['team_id']) ? $_GET['team_id'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Spring Shootout</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-aFq/bzH65dt+w6FI2ooMVUpc+21e0SRygnTpmBvdBgSdnuTN7QbdgL+OapgHtvPp" crossorigin="anonymous">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <div class="container">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark justify-content-center">
      <div id="nav-placeholder"></div>
    </nav>
  </div>
  <div class="poster">
    <img src="../assets/images/name.png" alt="Spring Shootout">
  </div>
  <div style="padding-top: 40px;">
    <hr>
  </div>
  <h1 class="text-center">Your Registration has been sent!</h1>
  <br>
  <h2 class="text-center">Thank you. Someone will reach out to you soon.</h2>
  <br>
  <div class="container text-center my-4 text-white">
    <p>If you would like to add additional contacts for your team, please click the button below.</p>
    <a href="/pages/contacts.php?team_id=<?php echo htmlspecialchars($teamId); ?>" class="btn btn-primary">
      Add Additional Contacts
    </a>
  </div>
  <!-- jQuery and Bootstrap JS -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  <script>
    $(document).ready(function() {
      $("#nav-placeholder").load("../includes/navbar.html");
    });
  </script> 
</body>
</html>

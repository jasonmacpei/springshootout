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
</head>
<body>

  <div class="container">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark justify-content-center">
    <div id="nav-placeholder"></div>
  </div>

  <!-- Content of the page -->
  <div class="poster">
    <img src="../assets/images/name.png">
  </div>

  <div style="padding-top: 50px;">
    <hr>
  </div>

<!-- login.php -->
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <form action="/scripts/php/authenticate.php" method="post" class="card bg-dark text-white p-4">
                <h2 class="text-center">Admin Login</h2>
                <div class="mb-3">
                    <label for="username" class="form-label">Username:</label>
                    <input type="text" class="form-control" id="username" name="username">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password:</label>
                    <input type="password" class="form-control" id="password" name="password">
                </div>
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
        </div>
    </div>
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

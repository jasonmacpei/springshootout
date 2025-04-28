<?php
// test_form.php
// A minimal HTML form to insert a new team into the 'teams' table.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Test Team Form</title>
</head>
<body>
    <h1>Add a New Team (Test)</h1>
    <form action="../scripts/php/test_submit.php" method="POST">
        <label for="team_name">Team Name:</label>
        <input type="text" name="team_name" id="team_name" required>
        <button type="submit">Submit</button>
    </form>
</body>
</html>
<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Debug Session</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: black;
            color: white;
        }
        pre {
            background-color: #222;
            padding: 15px;
            border: 1px solid #444;
            border-radius: 5px;
            overflow: auto;
        }
        .debug-box {
            background-color: #333;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        h1 {
            color: #fff;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            margin: 10px 0;
            background-color: #0275d8;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .btn-danger {
            background-color: #d9534f;
        }
        .btn-success {
            background-color: #5cb85c;
        }
    </style>
</head>
<body>
    <h1>Session Debug Information</h1>
    
    <div class="debug-box">
        <h2>Current Session Contents:</h2>
        <pre><?php print_r($_SESSION); ?></pre>
    </div>
    
    <div class="debug-box">
        <h2>Actions:</h2>
        <a href="/scripts/php/set_admin_role.php" class="btn btn-success">Set Admin Role</a>
        <a href="/pages/menu.php" class="btn">Try Menu Page</a>
        <a href="/pages/enter_results.php" class="btn">Back to Enter Results</a>
        <a href="/scripts/php/logout.php" class="btn btn-danger">Logout</a>
    </div>
    
    <div class="debug-box">
        <h2>Server Info:</h2>
        <p>PHP Version: <?php echo phpversion(); ?></p>
        <p>Session ID: <?php echo session_id(); ?></p>
    </div>
</body>
</html> 
<?php
session_start();

// Example credentials, replace with actual validation logic
$valid_username = 'admin';
$valid_password = 'pass123!';

// Second set of credentials for scorer
$scorer_username = 'scorer';
$scorer_password = 'shootout';

if (($_POST['username'] == $valid_username && $_POST['password'] == $valid_password) || 
    ($_POST['username'] == $scorer_username && $_POST['password'] == $scorer_password)) {
    $_SESSION['logged_in'] = true;

    // // Temporary line for debugging
    // echo 'Login successful. Redirecting to menu.php...';
    // // Wait for a few seconds and then redirect
    // header('Refresh: 5; URL=/pages/menu.php');


    header('Location: /pages/menu.php');
    exit;
} else {
    echo "Invalid username or password";
}
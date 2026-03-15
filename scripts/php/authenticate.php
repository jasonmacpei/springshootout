<?php
session_start();

// Example credentials, replace with actual validation logic
$valid_username = getenv('SPRING_SHOOTOUT_ADMIN_USERNAME') ?: 'admin';
$valid_password = getenv('SPRING_SHOOTOUT_ADMIN_PASSWORD') ?: '';

// Second set of credentials for scorer
$scorer_username = getenv('SPRING_SHOOTOUT_SCORER_USERNAME') ?: 'scorer';
$scorer_password = getenv('SPRING_SHOOTOUT_SCORER_PASSWORD') ?: '';

if ($_POST['username'] == $valid_username && $_POST['password'] == $valid_password) {
    $_SESSION['logged_in'] = true;
    $_SESSION['user_role'] = 'admin'; // Admin has full access
    
    header('Location: /pages/menu.php');
    exit;
} elseif ($_POST['username'] == $scorer_username && $_POST['password'] == $scorer_password) {
    $_SESSION['logged_in'] = true;
    $_SESSION['user_role'] = 'scorer'; // Scorer has limited access
    
    // Redirect directly to enter_results.php instead of menu for scorers
    header('Location: /pages/enter_results.php');
    exit;
} else {
    echo "Invalid username or password";
}

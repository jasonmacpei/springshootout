<?php
session_start();

// Force set the admin role
$_SESSION['logged_in'] = true;
$_SESSION['user_role'] = 'admin';

// Redirect to debug page
header('Location: /pages/debug_session.php');
exit;
?> 
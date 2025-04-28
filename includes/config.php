<?php
ini_set('error_log', '/home/lostan6/springshootout.ca/logs/new_error_log.txt');
ini_set('log_errors', 1);

// Always show errors + log them
ini_set('display_errors', 1);
error_reporting(E_ALL);


// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'lostan6_shootout');
define('DB_USER', 'lostan6_admin1');
define('DB_PASS', 'J0rdan23!');

// Email Configuration
define('SMTP_HOST', 'mail.springshootout.ca');
define('SMTP_PORT', 465);
define('SMTP_USER', 'jason@springshootout.ca');
define('SMTP_PASS', 'fabriw-kyctyq-foFva1');
define('ADMIN_EMAIL', 'jason@springshootout.ca');

// Site Configuration
define('SITE_NAME', 'Spring Shootout');
define('SITE_URL', 'https://www.springshootout.ca');

// Current year for registrations
define('CURRENT_YEAR', date('Y'));

// Division information - used for team listings
define('DIVISION_ORDER', json_encode(['u11', 'u12', 'u13']));
?>
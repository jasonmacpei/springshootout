<?php
function env_or_default($key, $default = '') {
    $value = getenv($key);
    return ($value !== false && $value !== '') ? $value : $default;
}

ini_set('error_log', env_or_default('SPRING_SHOOTOUT_ERROR_LOG', '/home/lostan6/springshootout.ca/logs/new_error_log.txt'));
ini_set('log_errors', 1);

// Always show errors + log them
ini_set('display_errors', 1);
error_reporting(E_ALL);


// Database Configuration
define('DB_HOST', env_or_default('SPRING_SHOOTOUT_DB_HOST', 'localhost'));
define('DB_NAME', env_or_default('SPRING_SHOOTOUT_DB_NAME', ''));
define('DB_USER', env_or_default('SPRING_SHOOTOUT_DB_USER', ''));
define('DB_PASS', env_or_default('SPRING_SHOOTOUT_DB_PASS', ''));

// Email Configuration
define('SMTP_HOST', env_or_default('SPRING_SHOOTOUT_SMTP_HOST', 'mail.springshootout.ca'));
define('SMTP_PORT', (int) env_or_default('SPRING_SHOOTOUT_SMTP_PORT', '465'));
define('SMTP_USER', env_or_default('SPRING_SHOOTOUT_SMTP_USER', ''));
define('SMTP_PASS', env_or_default('SPRING_SHOOTOUT_SMTP_PASS', ''));
define('ADMIN_EMAIL', env_or_default('SPRING_SHOOTOUT_ADMIN_EMAIL', 'hello@springshootout.ca'));

// Site Configuration
define('SITE_NAME', 'Spring Shootout');
define('SITE_URL', env_or_default('SPRING_SHOOTOUT_SITE_URL', 'https://www.springshootout.ca'));

// Current year for registrations
define('CURRENT_YEAR', date('Y'));

// Division information - used for team listings
define('DIVISION_ORDER', json_encode(['u11', 'u12', 'u13']));
?>

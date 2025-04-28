<?php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/home/lostan6/springshootout.ca/logs/new_error_log.txt');
error_reporting(E_ALL);

// Force an error:
error_log("Manual test: This error should be logged.");
echo "Test complete.";
?>
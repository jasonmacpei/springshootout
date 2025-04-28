<?php
// Set the error log path relative to the current directory of error_log_test.php
ini_set('error_log', __DIR__ . '/../../logs/error_log.txt');

// Write a test message to the error log
error_log('Test message for error logging.');

// Output a message to confirm that the script has run
echo 'If this message is printed, the script has run successfully.';
?>
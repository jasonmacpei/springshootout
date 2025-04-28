<?php
// Print the current working directory
echo "Current working directory: " . getcwd() . "<br>";

// Resolve and print the absolute path for the log file
$path = '/home/lostan6/springshootout.ca/logs/new_error_log.txt';
echo "Resolved path: " . realpath($path) . "<br>";
?>
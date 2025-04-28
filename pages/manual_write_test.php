<?php
// Include the configuration file which sets the error log path
require_once '/home/lostan6/springshootout.ca/includes/config.php';

// Check if configFile is set to 1
if ($configFile == 1) {
  echo 'Hit the config file';
} else {
  echo 'Config file is not hit';
}
echo '<br>';

// Write a test message to the error log
error_log('Test message for error logging at NEW ' . date("Y-m-d H:i:s"));

// Output a success message for the error_log() function
echo 'A test message should now be in the error_log.txt file set by config.php. WONDERFUL';

// No need to manually set $logFile or use file_put_contents() if you are just testing error_log()
?>


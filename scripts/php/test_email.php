<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include configuration
require_once '/home/lostan6/springshootout.ca/includes/config.php';
// Load PHPMailer via Composer autoload
require_once '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Output as plain text
header('Content-Type: text/plain');

echo "Email Test Script\n";
echo "=================\n\n";

// Display current settings
echo "Current SMTP Settings:\n";
echo "Host: " . SMTP_HOST . "\n";
echo "Port: " . SMTP_PORT . "\n";
echo "User: " . SMTP_USER . "\n";
echo "Pass: " . str_repeat('*', strlen(SMTP_PASS)) . "\n\n";

// Try both SMTPS (port 465) and STARTTLS (port 587)
$testConfigs = [
    [
        'name' => 'SSL/TLS (Default: SMTPS on port 465)',
        'secure' => PHPMailer::ENCRYPTION_SMTPS,
        'port' => 465
    ],
    [
        'name' => 'STARTTLS on port 587',
        'secure' => PHPMailer::ENCRYPTION_STARTTLS,
        'port' => 587
    ],
    [
        'name' => 'No encryption on port 25',
        'secure' => '',
        'port' => 25
    ]
];

foreach ($testConfigs as $config) {
    echo "Testing configuration: " . $config['name'] . "\n";
    
    $mail = new PHPMailer(true);
    try {
        // Turn on debug output
        $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Show server interaction
        
        // Capture debug output
        $debugOutput = '';
        $mail->Debugoutput = function($str, $level) use (&$debugOutput) {
            $debugOutput .= $str . "\n";
        };
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = $config['secure'];
        $mail->Port = $config['port'];
        $mail->Timeout = 20; // Timeout in seconds
        
        // Recipients
        $mail->setFrom(SMTP_USER, 'Test Sender');
        $mail->addAddress(SMTP_USER);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Test Email from ' . $config['name'];
        $mail->Body = 'This is a test email sent at ' . date('Y-m-d H:i:s');
        
        echo "Attempting to send...\n";
        $mail->send();
        
        echo "SUCCESS: Message sent using " . $config['name'] . "\n\n";
    } catch (Exception $e) {
        echo "FAILED: Could not send message using " . $config['name'] . "\n";
        echo "Error: {$mail->ErrorInfo}\n\n";
    }
    
    echo "Debug Output:\n$debugOutput\n";
    echo "--------------------------------------\n\n";
}

echo "Test complete. Check your email and the results above.\n";
?> 
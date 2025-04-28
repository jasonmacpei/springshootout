<?php
// test_email.php

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include Composer's autoload file (adjusted path)
require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Create a new PHPMailer instance
$mail = new PHPMailer(true);

try {
    // SMTP configuration
    $mail->isSMTP();
    $mail->Host       = 'mail.springshootout.ca';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'jason@springshootout.ca';
    $mail->Password   = 'J0rdan23!';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // or PHPMailer::ENCRYPTION_STARTTLS if needed
    $mail->Port       = 465; // adjust if necessary

    // Recipients
    $mail->setFrom('jason@springshootout.ca', 'Spring Shootout');
    $mail->addAddress('jasonmacpei@hotmail.com', 'Jason Mac'); // Replace with your test email

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Test Email from Spring Shootout';
    $mail->Body    = '<p>This is a test email sent from Spring Shootout.</p>';
    $mail->AltBody = 'This is a test email sent from Spring Shootout.';

    $mail->send();
    echo "Test email has been sent successfully.";

} catch (Exception $e) {
    echo "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
?>
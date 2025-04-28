<?php
require_once __DIR__ . '/vendor/autoload.php'; // Adjust the path to your autoload.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Only process POST reqeusts.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $to = $_POST['to'];
    $subject = $_POST['subject'];
    $body = $_POST['body'];
    $isHtml = isset($_POST['isHtml']) && $_POST['isHtml'] == 'on';

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'mail.springshootout.ca'; // Set the SMTP server to send through
        $mail->SMTPAuth = true; // Enable SMTP authentication
        $mail->Username = 'jason@springshootout.ca'; // SMTP username
        $mail->Password = 'J0rdan23!'; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Enable implicit TLS encryption
        $mail->Port = 465; // TCP port to connect to; use 465 for `PHPMailer::ENCRYPTION_SMTPS` above

        // Recipients
        $mail->setFrom('jason@springshootout.ca', 'Spring Shootout'); // TODO: Replace with your "from" address.
        $mail->addAddress($to); // Add a recipient

        // Content
        $mail->isHTML($isHtml); // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body); // Strip tags from body for non-HTML email clients.

        $mail->send();
        echo 'Message has been sent';
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
} else {
    echo 'Invalid request';
}

<?php
require_once __DIR__ . '/../../includes/config.php';
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
        $mail->Host = SMTP_HOST; // Set the SMTP server to send through
        $mail->SMTPAuth = true; // Enable SMTP authentication
        $mail->Username = SMTP_USER; // SMTP username
        $mail->Password = SMTP_PASS; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Enable implicit TLS encryption
        $mail->Port = SMTP_PORT; // TCP port to connect to; use 465 for `PHPMailer::ENCRYPTION_SMTPS` above

        // Recipients
        $mail->setFrom(ADMIN_EMAIL, 'Spring Shootout');
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

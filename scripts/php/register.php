<?php
// scripts/php/register.php

// Enable error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define a path for the custom error log file
$errorLogFile = __DIR__ . '/register_errors.log';

// A helper function for logging errors
function logError($message, $data = null) {
    global $errorLogFile;
    $logMessage = date('[Y-m-d H:i:s] ') . $message;
    if ($data !== null) {
        $logMessage .= ' | Data: ' . print_r($data, true);
    }
    file_put_contents($errorLogFile, $logMessage . "\n", FILE_APPEND);
}

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logError("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    header('Location: /pages/error_request_method.html');
    exit;
}

// Retrieve and sanitize POST fields
$teamName    = trim($_POST['team_name'] ?? '');
$contactName = trim($_POST['contact_name'] ?? '');
$roleId      = trim($_POST['contact_role'] ?? '');
$province    = trim($_POST['province'] ?? '');
$division    = trim($_POST['division'] ?? '');
$class       = trim($_POST['class'] ?? '');
$email       = trim($_POST['email'] ?? '');
$phone       = trim($_POST['phone'] ?? '');
$note        = trim($_POST['note'] ?? '');
$year        = date('Y');

// Basic validation
if (empty($teamName) || empty($contactName) || empty($roleId) ||
    empty($province) || empty($division) || empty($class) ||
    empty($email) || empty($phone)) {
    logError("Validation error: missing required fields", $_POST);
    header('Location: /pages/fail.html');
    exit;
}

// Include required files for database and email
require_once __DIR__ . '/db_connect.php';
// No need to include config.php again if db_connect already did it.
// Include Composer autoload using the relative path (update as needed)
require_once __DIR__ . '/../../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Helper function to send admin email
function sendAdminEmail($teamName, $contactName, $roleId, $province, $division, $class, $email, $phone, $note, $year) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;  // from config.php
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = SMTP_PORT;
        $mail->setFrom(SMTP_USER, SITE_NAME);
        $mail->addAddress(ADMIN_EMAIL);
        $mail->isHTML(false);
        $mail->Subject = "New Registration from $contactName";
        $mail->Body    = "A new registration has been received.\n\n" .
                         "Team: $teamName\n" .
                         "Contact Name: $contactName\n" .
                         "Role ID: $roleId\n" .
                         "Province: $province\n" .
                         "Division: $division\n" .
                         "Class: $class\n" .
                         "Email: $email\n" .
                         "Phone: $phone\n" .
                         "Note: $note\n" .
                         "Year: $year";
        $mail->send();
        logError("Admin email sent successfully");
    } catch (Exception $e) {
        logError("Error sending admin email: " . $mail->ErrorInfo);
        // Continue processing even if email fails
    }
}

// Helper function to send welcome email
function sendWelcomeEmail($contactName, $teamName, $recipientEmail) {
    $mail = new PHPMailer(true);
    try {
        // Attempt to fetch a welcome email template from DB, if available
        global $pdo;
        $stmtW = $pdo->query("SELECT subject, body FROM welcome_emails ORDER BY created_at DESC LIMIT 1");
        $welcomeData = $stmtW->fetch(PDO::FETCH_ASSOC);
        if ($welcomeData) {
            $subject  = $welcomeData['subject'];
            $template = $welcomeData['body'];
        } else {
            $subject  = "Welcome to Spring Shootout!";
            $template = "Dear {name},\n\nThank you for registering with team {team}.\n\nWe look forward to a great tournament!";
        }
    } catch (PDOException $e) {
        $subject  = "Welcome to Spring Shootout!";
        $template = "Dear {name},\n\nThank you for registering with team {team}.\n\nWe look forward to a great tournament!";
    }
    $body = str_replace(['{name}', '{team}'], [$contactName, $teamName], $template);
    
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = SMTP_PORT;
        $mail->setFrom(SMTP_USER, SITE_NAME);
        $mail->addAddress($recipientEmail, $contactName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = nl2br($body);
        $mail->AltBody = $body;
        $mail->send();
        logError("Welcome email sent successfully");
    } catch (Exception $e) {
        logError("Error sending welcome email: " . $mail->ErrorInfo);
        // Continue processing even if email fails
    }
}

// Begin database transaction
try {
    $pdo->beginTransaction();

    // 1. Insert or find contact
    $contactSQL = "SELECT contact_id FROM contacts WHERE contact_name = :cname AND email_address = :email LIMIT 1";
    $stmt = $pdo->prepare($contactSQL);
    $stmt->execute([':cname' => $contactName, ':email' => $email]);
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($contact) {
        $contactId = $contact['contact_id'];
    } else {
        $insertContactSQL = "INSERT INTO contacts (contact_name, email_address, phone_number)
        VALUES (:cname, :email, :phone)
        RETURNING contact_id";
            $stmtIns = $pdo->prepare($insertContactSQL);
            $stmtIns->execute([
            ':cname' => $contactName,
            ':email' => $email,
            ':phone' => $phone
            ]);
$contactId = $stmtIns->fetchColumn();
    }
    logError("Contact processed with ID: " . $contactId);

    // 2. Insert or find team
    $teamSQL = "SELECT team_id FROM teams WHERE team_name = :team_name LIMIT 1";
    $stmtTeam = $pdo->prepare($teamSQL);
    $stmtTeam->execute([':team_name' => $teamName]);
    $team = $stmtTeam->fetch(PDO::FETCH_ASSOC);
    if ($team) {
        $teamId = $team['team_id'];
    } else {
        $insertTeamSQL = "INSERT INTO teams (team_name)
                          VALUES (:team_name)
                          RETURNING team_id";
        $stmtTeamIns = $pdo->prepare($insertTeamSQL);
        $stmtTeamIns->execute([':team_name' => $teamName]);
        $teamId = $stmtTeamIns->fetchColumn();
    }
    logError("Team processed with ID: " . $teamId);

    // 3. Insert registration record
    $registrationSQL = "INSERT INTO registrations 
                        (team_id, division, class, province, note, year)
                        VALUES (:team_id, :division, :class, :province, :note, :year)";
    $stmtReg = $pdo->prepare($registrationSQL);
    $stmtReg->execute([
        ':team_id'  => $teamId,
        ':division' => $division,
        ':class'    => $class,
        ':province' => $province,
        ':note'     => $note,
        ':year'     => $year
    ]);
    logError("Registration record inserted");

    // 4. Link team and contact in team_contacts table
    $checkSQL = "SELECT role_id FROM team_contacts WHERE team_id = :tid AND contact_id = :cid LIMIT 1";
    $stmtCheck = $pdo->prepare($checkSQL);
    $stmtCheck->execute([':tid' => $teamId, ':cid' => $contactId]);
    $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    if ($existing) {
        if ($existing['role_id'] != $roleId) {
            $updateSQL = "UPDATE team_contacts SET role_id = :role_id WHERE team_id = :tid AND contact_id = :cid";
            $stmtUpdate = $pdo->prepare($updateSQL);
            $stmtUpdate->execute([':role_id' => $roleId, ':tid' => $teamId, ':cid' => $contactId]);
        }
    } else {
        $insertTCSQL = "INSERT INTO team_contacts (team_id, contact_id, role_id)
                        VALUES (:tid, :cid, :role_id)";
        $stmtTC = $pdo->prepare($insertTCSQL);
        $stmtTC->execute([':tid' => $teamId, ':cid' => $contactId, ':role_id' => $roleId]);
    }
    logError("Team_contacts link processed");

    // Commit the transaction
    $pdo->commit();
    logError("Database transaction committed successfully");

    // Send emails (admin and welcome)
    sendAdminEmail($teamName, $contactName, $roleId, $province, $division, $class, $email, $phone, $note, $year);
    sendWelcomeEmail($contactName, $teamName, $email);

    // Redirect to success page
    header("Location: /pages/success.php?team_id=" . $teamId);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    logError("Fatal error during registration: " . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    header("Location: /pages/fail.html");
    exit;
}
?>
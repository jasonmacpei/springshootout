<?php

// add_contact.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'db_connect.php'; // Ensure the correct path

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $team_id     = $_POST['team_id'] ?? '';
    $contactName = $_POST['contactName'] ?? '';
    $email       = $_POST['email'] ?? '';
    $phone       = $_POST['phone'] ?? '';
    $role_id     = $_POST['role'] ?? ''; // This should now be the role_id, not the role name

    if (!$team_id || !$contactName || !$email || !$phone || !$role_id) {
        die("All fields are required.");
    }

    try {
        $pdo->beginTransaction();
        
        // Insert into contacts table
        $contactSQL = "INSERT INTO contacts (contact_name, email_address, phone_number)
                       VALUES (:contactName, :email, :phone)
                       RETURNING contact_id";
        $stmt = $pdo->prepare($contactSQL);
        $stmt->execute([
            ':contactName' => $contactName,
            ':email'       => $email,
            ':phone'       => $phone
        ]);
        $newContactId = $stmt->fetchColumn();

    // Insert into team_contacts table with role_id
    $tcSQL = "INSERT INTO team_contacts (team_id, contact_id, role_id)
              VALUES (:teamId, :contactId, :roleId)";
        $tcStmt = $pdo->prepare($tcSQL);
        $tcStmt->execute([
            ':teamId'    => $team_id,
            ':contactId' => $newContactId,
            ':roleId'    => $role_id
        ]);
        
        $pdo->commit();
        header("Location: http://www.springshootout.ca/pages/success.php");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
} else {
    die("Invalid request method.");
}
?>
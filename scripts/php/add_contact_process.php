<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '/home/lostan6/springshootout.ca/includes/config.php';
require_once __DIR__ . '/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize input
    $contactName = isset($_POST['contact_name']) ? trim($_POST['contact_name']) : '';
    $emailAddress = isset($_POST['email_address']) ? trim($_POST['email_address']) : '';
    $phoneNumber = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '';
    $roleId = isset($_POST['role']) && !empty($_POST['role']) ? (int)$_POST['role'] : null;
    $teamId = isset($_POST['team']) && !empty($_POST['team']) ? (int)$_POST['team'] : null;

    // Validate required fields
    if (empty($contactName)) {
        http_response_code(400);
        echo "Contact name is required.";
        exit;
    }

    try {
        // Start transaction
        $pdo->beginTransaction();

        // First insert the contact without role (roles are managed in team_contacts)
        $sql = "INSERT INTO contacts (contact_name, email_address, phone_number) 
                VALUES (:contact_name, :email_address, :phone_number)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':contact_name' => $contactName,
            ':email_address' => $emailAddress,
            ':phone_number' => $phoneNumber
        ]);

        // Get the new contact's ID
        $contactId = $pdo->lastInsertId();

        // If both team and role were selected, create the team association
        if ($teamId && $roleId) {
            $linkSql = "INSERT INTO team_contacts (team_id, contact_id, role_id) 
                       VALUES (:team_id, :contact_id, :role_id)";
            
            $linkStmt = $pdo->prepare($linkSql);
            $linkStmt->execute([
                ':team_id' => $teamId,
                ':contact_id' => $contactId,
                ':role_id' => $roleId
            ]);
        }

        // Commit transaction
        $pdo->commit();

        echo "Contact added successfully.";
    } catch (PDOException $e) {
        // Roll back transaction on error
        $pdo->rollBack();
        http_response_code(500);
        echo "Error inserting contact: " . $e->getMessage();
        error_log("Error in add_contact_process.php: " . $e->getMessage());
    }
} else {
    http_response_code(405);
    echo "Invalid request method.";
}
?> 
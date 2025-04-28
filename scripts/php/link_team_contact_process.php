<?php
// link_team_contact_process.php
// This script handles linking (and optionally removing) a contact from a team.
// It expects POST parameters: team_id, contact_id, and optionally contact_role.
// For removal, it expects an additional parameter: action=remove.

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '/home/lostan6/springshootout.ca/includes/config.php';
require_once __DIR__ . '/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle contact removal
    if (isset($_POST['action']) && $_POST['action'] === 'remove') {
        $teamId = isset($_POST['team_id']) ? (int)$_POST['team_id'] : 0;
        $contactId = isset($_POST['contact_id']) ? (int)$_POST['contact_id'] : 0;

        if ($teamId <= 0 || $contactId <= 0) {
            http_response_code(400);
            echo "Invalid team or contact ID";
            exit;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM team_contacts WHERE team_id = :team_id AND contact_id = :contact_id");
            $stmt->execute([
                ':team_id' => $teamId,
                ':contact_id' => $contactId
            ]);
            echo "Contact removed from team successfully.";
        } catch (PDOException $e) {
            http_response_code(500);
            echo "Error removing contact: " . $e->getMessage();
            error_log("Error in link_team_contact_process.php (remove): " . $e->getMessage());
        }
        exit;
    }

    // Handle contact linking
    $teamId = isset($_POST['team_id']) ? (int)$_POST['team_id'] : 0;
    $contactId = isset($_POST['contact_id']) ? (int)$_POST['contact_id'] : 0;
    $roleId = isset($_POST['contact_role']) && !empty($_POST['contact_role']) ? (int)$_POST['contact_role'] : null;

    if ($teamId <= 0 || $contactId <= 0) {
        http_response_code(400);
        echo "Please select both a team and a contact.";
        exit;
    }

    try {
        // First check if this contact is already linked to this team
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM team_contacts WHERE team_id = :team_id AND contact_id = :contact_id");
        $checkStmt->execute([
            ':team_id' => $teamId,
            ':contact_id' => $contactId
        ]);
        
        if ($checkStmt->fetchColumn() > 0) {
            http_response_code(400);
            echo "This contact is already linked to this team.";
            exit;
        }

        // Verify the role_id exists if one was provided
        if ($roleId !== null) {
            $roleCheckStmt = $pdo->prepare("SELECT COUNT(*) FROM contact_roles WHERE role_id = :role_id");
            $roleCheckStmt->execute([':role_id' => $roleId]);
            
            if ($roleCheckStmt->fetchColumn() == 0) {
                http_response_code(400);
                echo "Invalid role selected.";
                exit;
            }
        }

        // Insert the new team-contact link with role_id
        $insertStmt = $pdo->prepare("INSERT INTO team_contacts (team_id, contact_id, role_id) VALUES (:team_id, :contact_id, :role_id)");
        $insertStmt->execute([
            ':team_id' => $teamId,
            ':contact_id' => $contactId,
            ':role_id' => $roleId
        ]);

        echo "Contact linked to team successfully.";
    } catch (PDOException $e) {
        http_response_code(500);
        echo "Error linking contact: " . $e->getMessage();
        error_log("Error in link_team_contact_process.php (link): " . $e->getMessage());
    }
} else {
    http_response_code(405);
    echo "Invalid request method.";
}
?>